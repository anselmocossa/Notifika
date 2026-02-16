<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendNotificationRequest;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Services\TemplateRenderer;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __construct(
        private TemplateRenderer $renderer
    ) {}

    public function send(SendNotificationRequest $request): JsonResponse
    {
        $subject = $request->input('subject', 'Notificação');
        $content = $request->input('content', '');
        $templateId = null;

        if ($request->filled('template')) {
            $rendered = $this->renderer->render(
                $request->input('template'),
                $request->input('data', [])
            );

            if (!$rendered) {
                return response()->json([
                    'error' => 'Template não encontrado ou inativo.',
                ], 404);
            }

            $subject = $rendered['subject'] ?: $subject;
            $content = $rendered['content'];
            $templateId = $rendered['template_id'];
        }

        $notification = Notification::create([
            'external_id' => Notification::generateExternalId(),
            'to' => $request->input('to'),
            'template_id' => $templateId,
            'subject' => $subject,
            'content' => $content,
            'status' => 'queued',
            'metadata' => $request->input('data'),
        ]);

        SendNotificationJob::dispatch($notification);

        return response()->json([
            'id' => $notification->external_id,
            'status' => 'queued',
            'created_at' => $notification->created_at->toIso8601String(),
        ], 201);
    }

    public function show(string $externalId): JsonResponse
    {
        $notification = Notification::where('external_id', $externalId)->first();

        if (!$notification) {
            return response()->json(['error' => 'Notificação não encontrada.'], 404);
        }

        return response()->json([
            'id' => $notification->external_id,
            'to' => $notification->to,
            'subject' => $notification->subject,
            'status' => $notification->status,
            'attempts' => $notification->attempts,
            'sent_at' => $notification->sent_at?->toIso8601String(),
            'failed_at' => $notification->failed_at?->toIso8601String(),
            'error_message' => $notification->error_message,
            'created_at' => $notification->created_at->toIso8601String(),
        ]);
    }

    public function index(): JsonResponse
    {
        $notifications = Notification::orderBy('created_at', 'desc')
            ->limit(50)
            ->get(['external_id', 'to', 'subject', 'status', 'sent_at', 'created_at']);

        return response()->json(['data' => $notifications]);
    }
}
