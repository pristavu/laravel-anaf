<?php

declare(strict_types=1);

use Pristavu\Anaf\Exceptions\AnafException;
use Pristavu\Anaf\Facades\Anaf;
use Pristavu\Anaf\Requests\Efactura\MessageStatusRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('can check a successful message status', function (): void {

    $mockClient = new MockClient([
        MessageStatusRequest::class => MockResponse::make(
            body: '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><header xmlns="mfp:anaf:dgti:efactura:stareMesajFactura:v1" stare="ok" id_descarcare="1234"/>',
            status: 200,
            headers: ['Content-Type' => 'application/xml']
        ),
    ]);

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $response = $connector->messageStatus(1234);

    expect($response)->toBeArray()
        ->and($response['success'])->toBeTrue()
        ->and($response['status'])->toBe('ok')
        ->and($response['download_id'])->toBe(1234);
});

it('can check a pending message status', function (): void {

    $mockClient = new MockClient([
        MessageStatusRequest::class => MockResponse::make(
            body: '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><header xmlns="mfp:anaf:dgti:efactura:stareMesajFactura:v1" stare="in prelucrare"/>',
            status: 200,
            headers: ['Content-Type' => 'application/xml']
        ),
    ]);

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $response = $connector->messageStatus(1234);

    expect($response)->toBeArray()
        ->and($response['success'])->toBeTrue()
        ->and($response['status'])->toBe('in prelucrare');
});

it('shows error message', function (): void {

    $mockClient = new MockClient([
        MessageStatusRequest::class => MockResponse::make(
            body: '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<header xmlns="mfp:anaf:dgti:efactura:stareMesajFactura:v1">
    <Errors errorMessage="Nu aveti dreptul de inteorgare pentru id_incarcare= 18"/>
</header>',
            status: 200,
            headers: ['Content-Type' => 'application/xml']
        ),
    ]);

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $response = $connector->messageStatus(1234);

    expect($response)->toBeArray()
        ->and($response['success'])->toBeFalse()
        ->and($response['errors'])->toBe('Nu aveti dreptul de inteorgare pentru id_incarcare= 18');

});

it('throws exception on invalid xml', function (): void {

    $mockClient = new MockClient([
        MessageStatusRequest::class => MockResponse::make(
            body: 'invalid xml',
            status: 200,
            headers: ['Content-Type' => 'application/xml']
        ),
    ]);

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $connector->messageStatus(123456);

})->throws(AnafException::class, 'Invalid XML response', 0);

it('throws exception on bad request', function (): void {

    $mockClient = new MockClient([
        MessageStatusRequest::class => MockResponse::make(
            body: [
                'timestamp' => '19-08-2021 11:59:29',
                'status' => 400,
                'error' => 'Bad Request',
                'message' => 'Parametrul id_incarcare este obligatoriu',
            ],
            status: 400,
            headers: ['Content-Type' => 'application/json']
        ),
    ]);

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $connector->messageStatus(123456);

})->throws(AnafException::class, 'Parametrul id_incarcare este obligatoriu', 400);
