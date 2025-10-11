<?php

declare(strict_types=1);

use Pristavu\Anaf\Connectors\OAuthConnector;
use Pristavu\Anaf\Facades\Anaf;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\OAuth2\GetAccessTokenRequest;
use Saloon\Http\OAuth2\GetRefreshTokenRequest;

it(description: 'returns the getAuthorizationUrl', closure: function (): void {

    $connector = Anaf::oauth(
        clientId: 'CLIENT_ID',
        clientSecret: 'CLIENT_SECRET',
        redirectUri: 'https://example.com/callback'
    );
    $state = 'STATE1234';
    $redirect = $connector->getAuthorizationUrl(state: $state, additionalQueryParameters: ['token_content_type' => 'jwt']);
    expect(value: $redirect)->toBe(expected: 'https://logincert.anaf.ro/anaf-oauth2/v1/authorize?response_type=code&client_id=CLIENT_ID&redirect_uri=https%3A%2F%2Fexample.com%2Fcallback&state=STATE1234&token_content_type=jwt');
});

test(description: 'client_id, client_secret and redirect_uri must be provided either as parameters or in the configuration', closure: function (): void {

    config()->set('oauth.anaf.client_id', null);
    config()->set('oauth.anaf.client_secret', null);
    config()->set('oauth.anaf.redirect_uri', null);

    $connector = Anaf::oauth();

})->throws(InvalidArgumentException::class);

it(description: 'it gets access token', closure: function (): void {

    $mockClient = new MockClient([
        GetAccessTokenRequest::class => MockResponse::make(
            body: [
                'access_token' => 'ACCESS_TOKEN',
                'expires_in' => 3600,
                'refresh_token' => 'REFRESH_TOKEN',
            ],
            status: 200,
            headers: ['Content-Type' => 'application/json']
        ),
    ]);

    $connector = new OAuthConnector(
        clientId: 'CLIENT_ID',
        clientSecret: 'CLIENT_SECRET',
        redirectUri: 'https://example.com/callback'
    );
    $connector->withMockClient($mockClient);

    $authenticator = $connector->getAccessToken('AUTH_CODE_1234');

    expect(value: $authenticator->getAccessToken())->toBe('ACCESS_TOKEN')
        ->and(value: $authenticator->getRefreshToken())->toBe('REFRESH_TOKEN')
        ->and(value: $authenticator->getExpiresAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('refreshes the access token', function (): void {

    $mockClient = new MockClient([
        GetRefreshTokenRequest::class => MockResponse::make(
            body: [
                'access_token' => 'NEW_ACCESS_TOKEN',
                'expires_in' => 7200,
                'refresh_token' => 'REFRESH_TOKEN',
            ],
            status: 200,
            headers: ['Content-Type' => 'application/json']
        ),
    ]);

    $connector = new OAuthConnector(
        clientId: 'CLIENT_ID',
        clientSecret: 'CLIENT_SECRET',
        redirectUri: 'https://example.com/callback'
    );
    $connector->withMockClient($mockClient);

    $authenticator = $connector->refreshAccessToken('OLD_REFRESH_TOKEN');

    expect(value: $authenticator->getAccessToken())->toBe('NEW_ACCESS_TOKEN')
        ->and(value: $authenticator->getRefreshToken())->toBe('REFRESH_TOKEN')
        ->and(value: $authenticator->getExpiresAt())->toBeInstanceOf(DateTimeImmutable::class);
});
