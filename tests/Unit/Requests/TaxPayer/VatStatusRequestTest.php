<?php

declare(strict_types=1);

use Pristavu\Anaf\Facades\Anaf;
use Pristavu\Anaf\Requests\TaxPayer\VatStatusRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('can successfully check VAT status', function (): void {

    $mockClient = new MockClient([
        VatStatusRequest::class => MockResponse::make(
            body: [

            ],
            status: 200,
            headers: ['Content-Type' => 'application/xml']
        ),
    ]);

    $connector = Anaf::taxPayer()->withMockClient($mockClient);
    $response = $connector->setTimeout(20)->vatStatus(cif: 29930516, date: '2024-01-01');

    expect($response)->toBeArray()
        ->and($response['success'])->toBeTrue();
});

it('throws exception on invalid cif', function (): void {
    $connector = Anaf::taxPayer();
    $connector->vatStatus(cif: 12, date: '2024-01-01');

})->throws(InvalidArgumentException::class, $message = 'The provided CIF is invalid.');
