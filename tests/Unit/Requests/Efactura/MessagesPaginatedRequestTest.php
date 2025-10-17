<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Pristavu\Anaf\Dto\Efactura\Message;
use Pristavu\Anaf\Dto\Efactura\Meta;
use Pristavu\Anaf\Enums\MessageType;
use Pristavu\Anaf\Exceptions\AnafException;
use Pristavu\Anaf\Facades\Anaf;
use Pristavu\Anaf\Requests\Efactura\MessagesPaginatedRequest;
use Pristavu\Anaf\Responses\Efactura\ErrorResponse;
use Pristavu\Anaf\Responses\Efactura\MessagesPaginatedResponse;
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

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $period = Carbon\CarbonPeriod::create(now()->subDays(2), now());
    $response = $connector->messagesPaginated(8000000000, $period, 1, $messageType);

    /** @var MessagesPaginatedResponse $response */
    expect($response)->toBeInstanceOf(MessagesPaginatedResponse::class)
        ->and($response->success)->toBeTrue()
        ->and($response->hash)->toBe('1234AA456')
        ->and($response->messages)->toBeInstanceOf(Collection::class)
        ->and($response->messages->count())->toBe(1)
        ->and($response->messages->first())->toBeInstanceOf(Message::class)
        ->and($response->messages->first()->cif)->toBe(8000000000)
        ->and($response->messages->first()->upload_id)->toBe(5001131297)
        ->and($response->messages->first()->download_id)->toBe(3001503294)
        ->and($response->messages->first()->created_at->format('Y-m-d H:i'))->toBe('2022-11-01 13:36')
        ->and($response->messages->first()->type)->toBe($messageType)
        ->and($response->messages->first()->description)->toBe('Factura cu id_incarcare=5001131297 emisa de cif_emitent=8000000000 pentru cif_beneficiar=3')
        ->and($response->meta)->toBeInstanceOf(Meta::class)
        ->and($response->meta->total)->toBe(1)
        ->and($response->meta->per_page)->toBe(500)
        ->and($response->meta->current_page)->toBe(1)
        ->and($response->meta->last_page)->toBe(1);

})->with([
    'message type SENT' => [MessageType::SENT, 'FACTURA TRIMISA'],
    'message type RECEIVED' => [MessageType::RECEIVED, 'FACTURA PRIMITA'],
    'message type ERROR' => [MessageType::ERROR, 'ERORI FACTURA'],
    'message type MESSAGE' => [MessageType::MESSAGE, 'ALTE MESAJE'],
]);

it('throws exception on invalid cif', function (): void {
    $connector = Anaf::eFactura('accessToken');

    $period = Carbon\CarbonPeriod::create(now()->subDays(2), now());
    $connector->messagesPaginated(cif: 12345, period: $period);
})->throws(InvalidArgumentException::class, $message = 'The provided CIF is invalid.');

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

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $period = Carbon\CarbonPeriod::create(now()->subDays(2), now());
    $response = $connector->messagesPaginated(cif: 29930516, period: $period);

    /* @var ErrorResponse $response */
    expect($response)->toBeInstanceOf(ErrorResponse::class)
        ->and($response->success)->toBeFalse()
        ->and($response->error)->toBe('Generic error message');

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

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $period = Carbon\CarbonPeriod::create(now()->subDays(2), now());
    $connector->messagesPaginated(cif: 29930516, period: $period);

})->throws(AnafException::class, 'Parametrii zile si cif sunt obligatorii', 400);
