<?php

declare(strict_types=1);

use Pristavu\Anaf\Facades\Anaf;
use Pristavu\Anaf\Requests\TaxPayer\BalanceSheetRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('can successfully retrieve balance sheet', function (): void {

    $mockClient = new MockClient([
        BalanceSheetRequest::class => MockResponse::make(
            body: [

            ],
            status: 200,
            headers: ['Content-Type' => 'application/xml']
        ),
    ]);

    $connector = Anaf::taxPayer()->withMockClient($mockClient);
    $response = $connector->setTimeout(20)->balanceSheet(cif: 29930516, year: 2024);

    expect($response)->toBeArray()
        ->and($response['success'])->toBeTrue();
});

it('throws exception on invalid cif', function (): void {
    $connector = Anaf::taxPayer();
    $connector->balanceSheet(cif: 12, year: 2024);

})->throws(InvalidArgumentException::class, $message = 'The provided CIF is invalid.');
