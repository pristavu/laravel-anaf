<?php

declare(strict_types=1);

use Pristavu\Anaf\Enums\MessageType;
use Pristavu\Anaf\Facades\Anaf;
use Pristavu\Anaf\Requests\Efactura\MessagesPaginatedRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('can retrieve efactura paginated messages', function ($messageType, $messageLabel): void {

    $mockClient = new MockClient([
        MessagesPaginatedRequest::class => MockResponse::make(
            body: [
                'mesaje' => [
                    [
                        'data_creare' => '202211011336',
                        'cif' => '8000000000',
                        'id_solicitare' => '5001131297',
                        'detalii' => 'Factura cu id_incarcare=5001131297 emisa de cif_emitent=8000000000 pentru cif_beneficiar=3',
                        'tip' => $messageLabel,
                        'id' => '3001503294',
                    ],
                ],
                'numar_inregistrari_in_pagina' => 1,
                'numar_total_inregistrari_per_pagina' => 500,
                'numar_total_inregistrari' => 1,
                'numar_total_pagini' => 1,
                'index_pagina_curenta' => 1,
                'serial' => '1234AA456',
                'cui' => '8000000000',
                'titlu' => 'Lista Mesaje disponibile din ultimele 1 zile',
            ],
            status: 200,
            headers: ['Content-Type' => 'application/json']
        ),
    ]);

    $connector = Anaf::efactura('accessToken')->withMockClient($mockClient);
    $period = Carbon\CarbonPeriod::create(now()->subDays(2), now());
    $response = $connector->messagesPaginated(8000000000, $period, 1, $messageType);

    expect($response)
        ->toBeArray()
        ->and($response['success'])->toBeTrue()
        ->and($response['hash'])->toBe('1234AA456')
        ->and(is_array($response['messages']))->toBeTrue()
        ->and(count($response['messages']))->toBe(1)
        ->and($response['messages'][0])->toBeInstanceOf(Pristavu\Anaf\Dto\Efactura\Message::class)
        ->and($response['messages'][0]->cif)->toBe(8000000000)
        ->and($response['messages'][0]->upload_id)->toBe(5001131297)
        ->and($response['messages'][0]->download_id)->toBe(3001503294)
        ->and($response['messages'][0]->created_at->format('Y-m-d H:i'))->toBe('2022-11-01 13:36')
        ->and($response['messages'][0]->type)->toBe($messageType)
        ->and($response['messages'][0]->description)->toBe('Factura cu id_incarcare=5001131297 emisa de cif_emitent=8000000000 pentru cif_beneficiar=3')
        ->and($response['meta'])->toBeArray()
        ->and($response['meta']['total'])->toBe(1)
        ->and($response['meta']['per_page'])->toBe(500)
        ->and($response['meta']['current_page'])->toBe(1)
        ->and($response['meta']['last_page'])->toBe(1);

})->with([
    'message type SENT' => [MessageType::SENT, 'FACTURA TRIMISA'],
    'message type RECEIVED' => [MessageType::RECEIVED, 'FACTURA PRIMITA'],
    'message type ERROR' => [MessageType::ERROR, 'ERORI FACTURA'],
    'message type MESSAGE' => [MessageType::MESSAGE, 'ALTE MESAJE'],
]);

it('handle anaf error', function (): void {

    $mockClient = new MockClient([
        MessagesPaginatedRequest::class => MockResponse::make(
            body: [
                'eroare' => 'Generic error message',
            ],
            status: 200,
            headers: ['Content-Type' => 'application/json']
        ),
    ]);

    $connector = Anaf::efactura('accessToken')->withMockClient($mockClient);
    $period = Carbon\CarbonPeriod::create(now()->subDays(2), now());
    $response = $connector->messagesPaginated(1234567890, $period);

    expect($response)
        ->toBeArray()
        ->and($response['success'])->toBeFalse()
        ->and($response['error'])->toBe('Generic error message');

});

it('throws exception on bad request', function (): void {

    $mockClient = new MockClient([
        MessagesPaginatedRequest::class => MockResponse::make(
            body: [
                'timestamp' => '19-08-2021 11:59:29',
                'status' => 400,
                'error' => 'Bad Request',
                'message' => 'Parametrii zile si cif sunt obligatorii',
            ],
            status: 400,
            headers: ['Content-Type' => 'application/json']
        ),
    ]);

    $connector = Anaf::efactura('accessToken')->withMockClient($mockClient);
    $period = Carbon\CarbonPeriod::create(now()->subDays(2), now());
    $response = $connector->messagesPaginated(1234567890, $period);

})->throws(Pristavu\Anaf\Exceptions\AnafException::class, 'Parametrii zile si cif sunt obligatorii', 400);
