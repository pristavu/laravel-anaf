<?php

declare(strict_types=1);

use Pristavu\Anaf\Facades\Anaf;
use Pristavu\Anaf\Requests\TaxPayer\BalanceSheetRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('can check a successful check a vatStatus', function (): void {

    $mockClient = new MockClient([
        BalanceSheetRequest::class => MockResponse::make(
            body: [

            ],
            status: 200,
            headers: ['Content-Type' => 'application/xml']
        ),
    ]);

    $connector = Anaf::taxPayer(29930516)->withMockClient($mockClient);
    $response = $connector->setTimeout(20)->balanceSheet(2024);

    expect($response)->toBeArray()
        ->and($response['success'])->toBeTrue();
});
