<?php

declare(strict_types=1);

namespace Pristavu\Anaf;

use InvalidArgumentException;
use Pristavu\Anaf\Connectors\EfacturaConnector;
use Pristavu\Anaf\Connectors\OAuthConnector;

final class Anaf
{
    /**
     * Create an OAuth token to be used with Anaf API.
     */
    public static function oauth(?string $clientId = null, ?string $clientSecret = null, ?string $redirectUri = null): OAuthConnector
    {
        $clientId ??= config('anaf.oauth.client_id');
        $clientSecret ??= config('anaf.oauth.client_secret');
        $redirectUri ??= config('anaf.oauth.redirect_uri');

        if (! $clientId || ! $clientSecret || ! $redirectUri) {
            throw new InvalidArgumentException('Client ID, Client Secret and Redirect URI must be provided either as parameters or in the configuration.');
        }

        return new OAuthConnector($clientId, $clientSecret, $redirectUri);
    }

    public function efactura(string $accessToken): EfacturaConnector
    {
        return new EfacturaConnector($accessToken);
    }
}
