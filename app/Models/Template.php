<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    use HasUuids;

    protected $fillable = [
        'client_id', 'channel', 'name', 'display_name', 'subject', 'content', 'variables', 'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function render(array $data = []): string
    {
        $content = $this->content;
        foreach ($data as $key => $value) {
            $content = str_replace("{{ {$key} }}", $value, $content);
        }

        return $content;
    }

    public function renderSubject(array $data = []): string
    {
        $subject = $this->subject ?? '';
        foreach ($data as $key => $value) {
            $subject = str_replace("{{ {$key} }}", $value, $subject);
        }

        return $subject;
    }
}
