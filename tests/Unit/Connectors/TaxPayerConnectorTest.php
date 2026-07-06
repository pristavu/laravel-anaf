<?php

declare(strict_types=1);

use Pristavu\Anaf\Connectors\TaxPayerConnector;
use Pristavu\Anaf\Requests\TaxPayer\VatStatusRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('can be instantiated with valid cif', function (): void {

    $connector = Pristavu\Anaf\Facades\Anaf::taxPayer();

    expect($connector)->toBeInstanceOf(TaxPayerConnector::class);
});

it('passes the configured proxy to requests', function (): void {
    config()->set('anaf.proxy', 'http://user:pass@proxy.example.com:8080');

    $mockClient = new MockClient([
        VatStatusRequest::class => MockResponse::make(body: [], status: 200),
    ]);

    $pending = Pristavu\Anaf\Facades\Anaf::taxPayer()
        ->withMockClient($mockClient)
        ->createPendingRequest(new VatStatusRequest(cif: 29930516, date: '2024-01-01'));

    expect($pending->config()->get('proxy'))->toBe('http://user:pass@proxy.example.com:8080');
});

it('sends no proxy config when none is configured', function (): void {
    $mockClient = new MockClient([
        VatStatusRequest::class => MockResponse::make(body: [], status: 200),
    ]);

    $pending = Pristavu\Anaf\Facades\Anaf::taxPayer()
        ->withMockClient($mockClient)
        ->createPendingRequest(new VatStatusRequest(cif: 29930516, date: '2024-01-01'));

    expect($pending->config()->all())->not->toHaveKey('proxy');
});
