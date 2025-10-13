<?php

declare(strict_types=1);

use Pristavu\Anaf\Exceptions\AnafException;
use Pristavu\Anaf\Facades\Anaf;
use Pristavu\Anaf\Requests\Efactura\UploadInvoiceRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('uploads an xml invoice', function (): void {

    $mockClient = new MockClient([
        UploadInvoiceRequest::class => MockResponse::make(
            body: '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<header xmlns="mfp:anaf:dgti:spv:respUploadFisier:v1" dateResponse="202108051140" ExecutionStatus="0" index_incarcare="3828"/>',
            status: 200,
            headers: ['Content-Type' => 'application/xml'],
        ),
    ]);

    $connector = Anaf::eInvoice('accessToken')->withMockClient($mockClient);
    $response = $connector->uploadInvoice(cif: 29930516, xml: 'XML', standard: Pristavu\Anaf\Enums\XmlStandard::UBL, isExternal: false, isSelfInvoice: false, isLegalEnforcement: false);

    expect($response)->toBeArray()
        ->and($response['success'])->toBeTrue()
        ->and($response['upload_id'])->toBe(3828);

});

it('handle anaf generic error', function (): void {

    $mockClient = new MockClient([
        UploadInvoiceRequest::class => MockResponse::make(
            body: '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<header xmlns="mfp:anaf:dgti:spv:respUploadFisier:v1" dateResponse="202210121019" ExecutionStatus="1">
    <Errors errorMessage="Generic error"/>
</header>',
            status: 200,
            headers: ['Content-Type' => 'application/xml'],
        ),
    ]);

    $connector = Anaf::eInvoice('accessToken')->withMockClient($mockClient);
    $response = $connector->uploadInvoice(cif: 29930516, xml: 'XML', standard: Pristavu\Anaf\Enums\XmlStandard::UBL, isExternal: true, isSelfInvoice: true, isLegalEnforcement: false);

    expect($response)->toBeArray()
        ->and($response['success'])->toBeFalse()
        ->and($response['error'])->toBe('Generic error');
});

it('throws exception on invalid cif', function (): void {
    $connector = Anaf::eInvoice('accessToken');

    $connector->uploadInvoice(cif: 1234567, xml: 'XML');
})->throws(InvalidArgumentException::class, $message = 'The provided CIF is invalid.');

it('throws exception on invalid xml', function (): void {

    $mockClient = new MockClient([
        UploadInvoiceRequest::class => MockResponse::make(
            body: 'invalid xml',
            status: 200,
            headers: ['Content-Type' => 'application/xml']
        ),
    ]);

    $xml = __DIR__.'/../../../Fixtures/Efactura/file.xml';

    $connector = Anaf::eInvoice('accessToken')->withMockClient($mockClient);
    $connector->uploadInvoice(cif: 29930516, xml: $xml, standard: Pristavu\Anaf\Enums\XmlStandard::UBL, isExternal: false, isSelfInvoice: false, isLegalEnforcement: false);

})->throws(AnafException::class, 'Invalid XML response', 0);

it('throws exception on bad request', function (): void {

    $mockClient = new MockClient([
        UploadInvoiceRequest::class => MockResponse::make(
            body: [
                'timestamp' => '2022-09-07T12:33:24.284+00:00',
                'status' => 400,
                'error' => 'Bad Request',
                'message' => 'Trebuie sa aveti atasat in request un fisier de tip xml',
            ],
            status: 400,
            headers: ['Content-Type' => 'application/json']
        ),
    ]);

    $connector = Anaf::eInvoice('accessToken')->withMockClient($mockClient);
    $connector->uploadInvoice(cif: 29930516, xml: 'XML', standard: Pristavu\Anaf\Enums\XmlStandard::UBL, isExternal: false, isSelfInvoice: false, isLegalEnforcement: false);

})->throws(AnafException::class, 'Trebuie sa aveti atasat in request un fisier de tip xml', 400);
