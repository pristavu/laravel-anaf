<?php

declare(strict_types=1);

namespace Pristavu\Anaf;

use InvalidArgumentException;
use Pristavu\Anaf\Connectors\CompanyConnector;
use Pristavu\Anaf\Connectors\EfacturaConnector;
use Pristavu\Anaf\Connectors\OAuthConnector;

final class Anaf
{
    /**
     * Create an OAuth token to be used with Anaf API.
     *
     * @param string|null $clientId The OAuth2 client ID. If null, it will be fetched from the configuration.
     * @param string|null $clientSecret The OAuth2 client secret. If null, it will be fetched from the configuration.
     * @param string|null $redirectUri The OAuth2 redirect URI. If null, it will be fetched from the configuration.
     * @return OAuthConnector
     *
     * @throws InvalidArgumentException If any of the required parameters are missing.
     *
     * @see https://static.anaf.ro/static/10/Anaf/Informatii_R/API/Oauth_procedura_inregistrare_aplicatii_portal_ANAF.pdf
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

    /**
     * Create an Efactura connector to interact with the Efactura API.
     *
     * @param string $accessToken The OAuth2 access token.
     * @return EfacturaConnector
     *
     * @see https://mfinante.gov.ro/static/10/eFactura/prezentare%20api%20efactura.pdf
     */
    public function efactura(string $accessToken): EfacturaConnector
    {
        return new EfacturaConnector($accessToken);
    }

    /**
     * Create a Company connector to interact with the Company API.
     *
     * @param int $cif The Fiscal Identification Code of the company.
     * @return CompanyConnector
     *
     * @see https://static.anaf.ro/static/10/Anaf/Informatii_R/Servicii_web/doc_WS_V9.txt
     */
    public function company(int $cif): CompanyConnector
    {
        return new CompanyConnector($cif);
    }
}
