<?php

declare(strict_types=1);

use Pristavu\Anaf\Connectors\EfacturaConnector;

it(description: 'returns correct base url', closure: function (bool $isTesting, string $baseUrl): void {

    $connector = new EfacturaConnector('TEST_API_KEY');
    $isTesting ? $connector->inTestMode() : $connector->inLiveMode();

    expect(value: $connector->resolveBaseUrl())->toBe(expected: $baseUrl);
})->with([
    'test' => [true, 'https://api.anaf.ro/test/FCTEL/rest'],
    'prod' => [false, 'https://api.anaf.ro/prod/FCTEL/rest'],
]);

it(description: 'sets `Accept` header to application/json', closure: function (): void {
    $connector = new EfacturaConnector('TEST_API_KEY');

    expect(value: $connector->headers()->get(key: 'Accept'))->toBe(expected: 'application/json');
});

it(description: 'sets `Content-Type` header to application/json', closure: function (): void {
    $connector = new EfacturaConnector('TEST_API_KEY');

    expect(value: $connector->headers()->get(key: 'Content-Type'))->toBe(expected: 'application/json');
});

it(description: 'sets `Bearer token` authentication header', closure: function (): void {

    $connector = new EfacturaConnector('TEST_API_KEY');

    expect(value: $connector->getAuthenticator()->token)->toBe(expected: 'TEST_API_KEY')
        ->and(value: $connector->getAuthenticator()->prefix)->toBe(expected: 'Bearer');
});
