<?php

declare(strict_types=1);

use Pristavu\Anaf\Exceptions\AnafException;
use Pristavu\Anaf\Facades\Anaf;
use Pristavu\Anaf\Requests\Efactura\DownloadInvoiceRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('can download an invoice', function (): void {

    $mockClient = new MockClient([
        DownloadInvoiceRequest::class => MockResponse::make(
            body: '%ZIPFILE%',
            status: 200,
            headers: ['Content-Type' => 'application/pdf'],
        ),
    ]);

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $response = $connector->downloadInvoice(123456);

    expect($response)->toBeArray()
        ->and($response['success'])->toBeTrue()
        ->and($response['content'])->toBe('%ZIPFILE%');

});

it('handle anaf error', function (): void {

    $mockClient = new MockClient([
        DownloadInvoiceRequest::class => MockResponse::make(
            body: [
                'eroare' => 'Generic error message',
            ],
            status: 200,
            headers: ['Content-Type' => 'application/json']
        ),
    ]);

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $response = $connector->invalidateCache()->disableCaching()->downloadInvoice(123456);

    expect($response)
        ->toBeArray()
        ->and($response['success'])->toBeFalse()
        ->and($response['error'])->toBe('Generic error message');

});

it('throws exception on bad request', function (): void {

    $mockClient = new MockClient([
        DownloadInvoiceRequest::class => MockResponse::make(
            body: [
                'timestamp' => '19-08-2021 11:59:29',
                'status' => 400,
                'error' => 'Bad Request',
                'message' => 'Parametrul id este obligatoriu',
            ],
            status: 400,
            headers: ['Content-Type' => 'application/json']
        ),
    ]);

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $connector->invalidateCache()->disableCaching()->downloadInvoice(123456);

})->throws(AnafException::class, 'Parametrul id este obligatoriu', 400);
