<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Pristavu\Anaf\Database\Factories\AccessTokenFactory;
use Saloon\Http\Auth\AccessTokenAuthenticator;

/** @codeCoverageIgnore  */
final class AccessToken extends Model
{
    /** @use HasFactory<AccessTokenFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    /**
     * Returns an AccessTokenAuthenticator instance for use with Saloon connectors.
     */
    public function authenticator(): AccessTokenAuthenticator
    {
        return new AccessTokenAuthenticator(
            accessToken: $this->attributes['access_token'],
            refreshToken: $this->attributes['refresh_token'],
            expiresAt: $this->attributes['expires_at']
        );
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => 'string',
            'token' => 'string',
            'refresh_token' => 'string',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
