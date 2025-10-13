<?php

declare(strict_types=1);

use Pristavu\Anaf\Facades\Anaf;
use Pristavu\Anaf\Requests\TaxPayer\VatStatusRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('can check a successful check a vatStatus', function (): void {

    $mockClient = new MockClient([
        VatStatusRequest::class => MockResponse::make(
            body: [

            ],
            status: 200,
            headers: ['Content-Type' => 'application/xml']
        ),
    ]);

    $connector = Anaf::taxPayer(29930516)->withMockClient($mockClient);
    $response = $connector->setTimeout(20)->vatStatus('2024-01-01');

    expect($response)->toBeArray()
        ->and($response['success'])->toBeTrue();
});
