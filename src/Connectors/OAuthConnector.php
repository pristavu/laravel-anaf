<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Connectors;

use Saloon\Helpers\OAuth2\OAuthConfig;
use Saloon\Http\Connector;
use Saloon\Http\OAuth2\GetAccessTokenRequest;
use Saloon\Http\Request;
use Saloon\Traits\OAuth2\AuthorizationCodeGrant;

final class OAuthConnector extends Connector
{
    use AuthorizationCodeGrant;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
    ) {
        //
    }

    public function resolveBaseUrl(): string
    {
        return 'https://logincert.anaf.ro/anaf-oauth2/v1';
    }

    protected function defaultOauthConfig(): OAuthConfig
    {
        return OAuthConfig::make()
            ->setClientId($this->clientId)
            ->setClientSecret($this->clientSecret)
            ->setDefaultScopes([])
            ->setRedirectUri($this->redirectUri)
            // Relative to the connector base URL — Saloon v4 rejects
            // absolute endpoint URLs (SSRF hardening, CVE-2026-33182).
            ->setAuthorizeEndpoint('authorize')
            ->setTokenEndpoint('token')
            ->setRequestModifier(function (Request $request): void {
                if ($request instanceof GetAccessTokenRequest) {
                    $request->body()->add('token_content_type', 'jwt');
                }

            });
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }
}
