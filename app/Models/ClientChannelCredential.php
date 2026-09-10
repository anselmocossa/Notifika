<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientChannelCredential extends Model
{
    use HasUuids;

    protected $fillable = [
        'client_id', 'channel', 'driver', 'credentials', 'sender', 'is_active',
    ];

    protected $casts = [
        // Cifradas na base de dados (CA-12): nunca ficam em claro nem na BD nem em logs.
        'credentials' => 'encrypted:array',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'credentials',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
