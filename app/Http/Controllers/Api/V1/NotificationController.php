<?php

namespace App\Http\Controllers\Api\V1;

use App\Channels\ChannelManager;
use App\Channels\Exceptions\ChannelNotConfiguredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendNotificationRequest;
use App\Jobs\SendNotificationJob;
use App\Models\Client;
use App\Models\Notification;
use App\Services\TemplateRenderer;
use App\Support\Sms\BrandGuard;
use App\Support\Sms\PhoneNumber;
use App\Support\Sms\SmsEncoding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private TemplateRenderer $renderer,
        private ChannelManager $channelManager,
    ) {}

    public function send(SendNotificationRequest $request): JsonResponse
    {
        $client = Client::fromAuthenticatable($request->user());
        $channel = $request->channel();

        // CA-04: resolve antes de gravar nada — sem driver, 422 imediato em
        // vez de uma notificação 'queued' que nunca vai ser enviada.
        try {
            $this->channelManager->resolve($client, $channel);
        } catch (ChannelNotConfiguredException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $subject = $request->input('subject', 'Notificação');
        $content = $request->input('content', '');
        $templateId = null;

        if ($request->filled('template')) {
            $rendered = $this->renderer->render(
                $request->input('template'),
                $request->input('data', [])
            );

            if (! $rendered) {
                return response()->json([
                    'error' => 'Template não encontrado ou inactivo.',
                ], 404);
            }

            $subject = $rendered['subject'] ?: $subject;
            $content = $rendered['content'];
            $templateId = $rendered['template_id'];
        }

        $to = $request->input('to');
        $encoding = null;
        $segments = null;

        if ($channel === 'sms') {
            $to = PhoneNumber::forMozambique($to)->value();
            $content = BrandGuard::apply($content, $client->brand_name);
            $result = SmsEncoding::analyze($content);
            $encoding = $result->encoding;
            $segments = $result->segments;
        }

        $notification = Notification::create([
            'external_id' => Notification::generateExternalId(),
            'client_id' => $client->id,
            'channel' => $channel,
            'to' => $to,
            'template_id' => $templateId,
            'subject' => $channel === 'email' ? $subject : null,
            'content' => $content,
            'status' => 'queued',
            'metadata' => $request->input('data'),
            'encoding' => $encoding,
            'segments' => $segments,
        ]);

        SendNotificationJob::dispatch($notification);

        $response = [
            'id' => $notification->external_id,
            'status' => 'queued',
            'channel' => $notification->channel,
            'created_at' => $notification->created_at->toIso8601String(),
        ];

        if ($channel === 'sms') {
            $response['encoding'] = $notification->encoding;
            $response['segments'] = $notification->segments;
        }

        return response()->json($response, 201);
    }

    public function show(Request $request, string $externalId): JsonResponse
    {
        $client = Client::fromAuthenticatable($request->user());

        $notification = Notification::where('external_id', $externalId)
            ->where('client_id', $client->id)
            ->first();

        if (! $notification) {
            return response()->json(['error' => 'Notificação não encontrada.'], 404);
        }

        return response()->json([
            'id' => $notification->external_id,
            'to' => $notification->to,
            'channel' => $notification->channel,
            'subject' => $notification->subject,
            'status' => $notification->status,
            'attempts' => $notification->attempts,
            'sent_at' => $notification->sent_at?->toIso8601String(),
            'failed_at' => $notification->failed_at?->toIso8601String(),
            'error_message' => $notification->error_message,
            'created_at' => $notification->created_at->toIso8601String(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $client = Client::fromAuthenticatable($request->user());

        $notifications = Notification::where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get(['external_id', 'to', 'channel', 'subject', 'status', 'sent_at', 'created_at']);

        return response()->json(['data' => $notifications]);
    }
}
