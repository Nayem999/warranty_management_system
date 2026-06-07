<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MicrosoftOAuthToken extends Model
{
    protected $table = 'microsoft_oauth_tokens';

    protected $fillable = [
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return !$this->expires_at || $this->expires_at->isPast();
    }
}
