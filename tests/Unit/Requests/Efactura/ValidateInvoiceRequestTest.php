<?php

declare(strict_types=1);

use Pristavu\Anaf\Enums\DocumentStandard;
use Pristavu\Anaf\Exceptions\AnafException;
use Pristavu\Anaf\Facades\Anaf;
use Pristavu\Anaf\Requests\Efactura\ValidateInvoiceRequest;
use Pristavu\Anaf\Responses\Efactura\ValidateErrorResponse;
use Pristavu\Anaf\Responses\Efactura\ValidateInvoiceResponse;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('validate correct xml invoice', function (): void {

    $mockClient = new MockClient([
        ValidateInvoiceRequest::class => MockResponse::make(
            body: [
                'stare' => 'ok',
                'trace_id' => 'c8c9da20-843f-4130-91d1-4ee8b0baaa30',
            ],
            status: 200,
            headers: ['Content-Type' => 'application/json']
        ),
    ]);

    $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2">
   <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
</Invoice>
XML;

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $response = $connector->validateInvoice($xml, DocumentStandard::FACT1);

    expect($response)->toBeInstanceOf(ValidateInvoiceResponse::class)
        ->and($response->success)->toBeTrue()
        ->and($response->trace_id)->toBe('c8c9da20-843f-4130-91d1-4ee8b0baaa30');

});

it('handle anaf error', function (): void {

    $mockClient = new MockClient([
        ValidateInvoiceRequest::class => MockResponse::make(
            body: [
                'stare' => 'nok',
                'Messages' => [
                    ['message' => 'XML validation error'],
                    ['message' => 'Another error'],
                ],
                'trace_id' => 'c8c9da20-843f-4130-91d1-4ee8b0baaa30',
            ],
            status: 200,
            headers: ['Content-Type' => 'application/json'],
        ),
    ]);

    $xml = __DIR__.'/../../../Fixtures/Efactura/file.xml';

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $response = $connector->validateInvoice($xml);

    /** @var ValidateErrorResponse $response */
    expect($response)->toBeInstanceOf(ValidateErrorResponse::class)
        ->and($response->success)->toBeFalse()
        ->and($response->errors)->toBeArray()
        ->and(count($response->errors))->toBe(2)
        ->and($response->trace_id)->toBe('c8c9da20-843f-4130-91d1-4ee8b0baaa30');
});

it('throws exception on bad request', function (): void {

    $mockClient = new MockClient([
        ValidateInvoiceRequest::class => MockResponse::make(
            body: [
                'timestamp' => '2022-09-07T12:33:24.284+00:00',
                'status' => 404,
                'error' => 'Not Found',
                'path' => '/FCTEL/rest/validare/',
            ],
            status: 400,
            headers: ['Content-Type' => 'application/json']
        ),
    ]);

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $connector->validateInvoice('1234567890');

})->throws(AnafException::class, 'Not Found', 404);
