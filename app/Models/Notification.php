<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Notification extends Model
{
    use HasUuids;

    protected $fillable = [
        'external_id', 'client_id', 'channel', 'driver', 'to', 'template_id', 'subject', 'content',
        'status', 'metadata', 'attempts', 'max_attempts',
        'provider_message_id', 'encoding', 'segments', 'cost', 'cost_currency',
        'sent_at', 'failed_at', 'error_message',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'segments' => 'integer',
        'cost' => 'decimal:4',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public static function generateExternalId(): string
    {
        return 'ntf_'.Str::random(12);
    }

    public function markAsSent(array $attributes = []): void
    {
        $this->update(array_merge([
            'status' => 'sent',
            'sent_at' => now(),
        ], $attributes));
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $error,
        ]);
    }
}
