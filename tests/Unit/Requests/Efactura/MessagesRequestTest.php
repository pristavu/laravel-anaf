<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Pristavu\Anaf\Enums\MessageType;
use Pristavu\Anaf\Exceptions\AnafException;
use Pristavu\Anaf\Facades\Anaf;
use Pristavu\Anaf\Requests\Efactura\MessagesRequest;
use Pristavu\Anaf\Responses\Efactura\ErrorResponse;
use Pristavu\Anaf\Responses\Efactura\MessagesResponse;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('can retrieve efactura messages', function ($messageType, $messageLabel): void {

    $mockClient = new MockClient([
        MessagesRequest::class => MockResponse::make(
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
                'serial' => '1234AA456',
                'cui' => '8000000000',
                'titlu' => 'Lista Mesaje disponibile din ultimele 1 zile',
            ],
            status: 200,
            headers: ['Content-Type' => 'application/json']
        ),
    ]);

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $response = $connector->messages(8000000000, 1, $messageType);

    /** @var MessagesResponse $response */
    expect($response)->toBeInstanceOf(MessagesResponse::class)
        ->and($response->success)->toBeTrue()
        ->and($response->hash)->toBe('1234AA456')
        ->and($response->messages)->toBeInstanceOf(Collection::class)
        ->and($response->messages->count())->toBe(1)
        ->and($response->messages->first())->toBeInstanceOf(Pristavu\Anaf\Dto\Efactura\Message::class)
        ->and($response->messages->first()->cif)->toBe(8000000000)
        ->and($response->messages->first()->upload_id)->toBe(5001131297)
        ->and($response->messages->first()->download_id)->toBe(3001503294)
        ->and($response->messages->first()->created_at->format('Y-m-d H:i'))->toBe('2022-11-01 13:36')
        ->and($response->messages->first()->type)->toBe($messageType)
        ->and($response->messages->first()->description)->toBe('Factura cu id_incarcare=5001131297 emisa de cif_emitent=8000000000 pentru cif_beneficiar=3');

})->with([
    'message type SENT' => [MessageType::SENT, 'FACTURA TRIMISA'],
    'message type RECEIVED' => [MessageType::RECEIVED, 'FACTURA PRIMITA'],
    'message type ERROR' => [MessageType::ERROR, 'ERORI FACTURA'],
    'message type MESSAGE' => [MessageType::MESSAGE, 'ALTE MESAJE'],
]);

it('handle anaf error', function (): void {

    $mockClient = new MockClient([
        MessagesRequest::class => MockResponse::make(
            body: [
                'eroare' => 'Generic error message',
            ],
            status: 200,
            headers: ['Content-Type' => 'application/json']
        ),
    ]);

    $connector = Anaf::eFactura('accessToken')->withMockClient($mockClient);
    $response = $connector->messages(cif: 29930516, days: 1);

    /** @var ErrorResponse $response */
    expect($response)->toBeInstanceOf(ErrorResponse::class)
        ->and($response->success)->toBeFalse()
        ->and($response->error)->toBe('Generic error message');

});

it('throws exception on invalid cif', function (): void {
    $connector = Anaf::eFactura('accessToken');
    $connector->messages(cif: 12345, days: 1);
})->throws(InvalidArgumentException::class, $message = 'The provided CIF is invalid.');

it('throws exception on bad request', function (): void {

    $mockClient = new MockClient([
        MessagesRequest::class => MockResponse::make(
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
    $connector->messages(cif: 29930516, days: 1);

})->throws(AnafException::class, 'Parametrii zile si cif sunt obligatorii', 400);
