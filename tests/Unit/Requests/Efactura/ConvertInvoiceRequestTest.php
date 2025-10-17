<?php

declare(strict_types=1);

use Pristavu\Anaf\Enums\DocumentStandard;
use Pristavu\Anaf\Exceptions\AnafException;
use Pristavu\Anaf\Facades\Anaf;
use Pristavu\Anaf\Requests\Efactura\ConvertInvoiceRequest;
use Pristavu\Anaf\Responses\Efactura\ConvertInvoiceErrorResponse;
use Pristavu\Anaf\Responses\Efactura\ConvertInvoiceResponse;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('converts xml invoice to pdf', function (): void {

    $mockClient = new MockClient([
        ConvertInvoiceRequest::class => MockResponse::make(
            body: '%PDF-1.4',
            status: 200,
            headers: ['Content-Type' => 'application/pdf'],
        ),
    ]);

    $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2">
   <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
</Invoice>
XML;

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $response = $connector->setTimeout(20)->convertInvoice(xml: $xml, standard: DocumentStandard::FACT1, withoutValidation: true);

    expect($response)->toBeInstanceOf(ConvertInvoiceResponse::class)
        ->and($response->success)->toBeTrue()
        ->and($response->content)->toBe('%PDF-1.4');

});

it('handle anaf error', function (): void {

    $mockClient = new MockClient([
        ConvertInvoiceRequest::class => MockResponse::make(
            body: [
                'stare' => 'nok',
                'Messages' => [
                    ['message' => 'A aparut o eroare tehnica. Cod: 100'],
                ],
                'trace_id' => 'c8c9da20-843f-4130-91d1-4ee8b0baaa30',
            ],
            status: 200,
            headers: ['Content-Type' => 'application/json'],
        ),
    ]);

    $xml = __DIR__.'/../../../Fixtures/Efactura/file.xml';

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $response = $connector->convertInvoice(xml: $xml, standard: DocumentStandard::FCN, withoutValidation: true);

    expect($response)->toBeInstanceOf(ConvertInvoiceErrorResponse::class)
        ->and($response->success)->toBeFalse()
        ->and($response->errors)->toBeArray()
        ->and(count($response->errors))->toBe(1)
        ->and($response->trace_id)->toBe('c8c9da20-843f-4130-91d1-4ee8b0baaa30');
});

it('throws exception on bad request', function (): void {

    $mockClient = new MockClient([
        ConvertInvoiceRequest::class => MockResponse::make(
            body: [
                'timestamp' => '2022-09-07T12:33:24.284+00:00',
                'status' => 404,
                'error' => 'Not Found',
                'path' => '/FCTEL/rest/transformare/',
            ],
            status: 400,
            headers: ['Content-Type' => 'application/json']
        ),
    ]);

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $connector->convertInvoice('1234567890');

})->throws(AnafException::class, 'Not Found', 404);
