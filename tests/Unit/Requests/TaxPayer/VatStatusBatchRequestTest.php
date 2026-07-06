<?php

declare(strict_types=1);

use Pristavu\Anaf\Exceptions\AnafException;
use Pristavu\Anaf\Facades\Anaf;
use Pristavu\Anaf\Requests\TaxPayer\VatStatusBatchRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('can check VAT status for a batch of cifs', function (): void {

    $mockClient = new MockClient([
        VatStatusBatchRequest::class => MockResponse::make(
            body: [
                'cod' => 200,
                'message' => 'SUCCESS',
                'found' => [
                    ['date_generale' => ['cui' => 29930516, 'denumire' => 'FIRMA UNU']],
                    ['date_generale' => ['cui' => 1590082, 'denumire' => 'FIRMA DOI']],
                ],
                'notFound' => [14399840],
            ],
            status: 200,
            headers: ['Content-Type' => 'application/json']
        ),
    ]);

    $connector = Anaf::taxPayer()->withMockClient($mockClient);
    $response = $connector->vatStatuses(cifs: [29930516, 1590082, 14399840], date: '2024-01-01');

    expect($response)->toBeArray()
        ->and($response['success'])->toBeTrue()
        ->and($response['found'])->toHaveCount(2)
        ->and($response['not_found'])->toBe([14399840]);
});

it('sends one body entry per cif with the query date', function (): void {

    $request = new VatStatusBatchRequest(cifs: [29930516, 1590082], date: '2024-01-01');

    expect($request->body()->all())->toBe([
        ['cui' => 29930516, 'data' => '2024-01-01'],
        ['cui' => 1590082, 'data' => '2024-01-01'],
    ]);
});

it('normalizes prefixed string cifs into numeric body entries', function (): void {

    $request = new VatStatusBatchRequest(cifs: ['RO29930516', 1590082], date: '2024-01-01');

    expect($request->body()->all())->toBe([
        ['cui' => 29930516, 'data' => '2024-01-01'],
        ['cui' => 1590082, 'data' => '2024-01-01'],
    ]);
});

it('throws exception when a cif in the batch is invalid', function (): void {
    $connector = Anaf::taxPayer();
    $connector->vatStatuses(cifs: [29930516, 12], date: '2024-01-01');

})->throws(InvalidArgumentException::class, 'The provided CIF is invalid.');

it('throws exception when the batch exceeds 100 cifs', function (): void {
    $connector = Anaf::taxPayer();
    $connector->vatStatuses(cifs: array_fill(0, 101, 29930516), date: '2024-01-01');

})->throws(InvalidArgumentException::class, 'A batch can contain at most 100 CIFs.');

it('throws exception when the batch is empty', function (): void {
    $connector = Anaf::taxPayer();
    $connector->vatStatuses(cifs: [], date: '2024-01-01');

})->throws(InvalidArgumentException::class, 'A batch must contain at least one CIF.');

it('surfaces the anaf eroare body in thrown exceptions', function (): void {

    $mockClient = new MockClient([
        VatStatusBatchRequest::class => MockResponse::make(
            body: ['eroare' => 'Limita de 1 request/secunda a fost depasita', 'cod' => 429],
            status: 429,
            headers: ['Content-Type' => 'application/json']
        ),
    ]);

    $connector = Anaf::taxPayer()->withMockClient($mockClient);

    expect(fn (): array|object => $connector->vatStatuses(cifs: [29930516]))
        ->toThrow(AnafException::class, 'Limita de 1 request/secunda a fost depasita');
});

it('reports the anaf error body as an unsuccessful response', function (): void {

    $mockClient = new MockClient([
        VatStatusBatchRequest::class => MockResponse::make(
            body: ['eroare' => 'Serviciu indisponibil'],
            status: 200,
            headers: ['Content-Type' => 'application/json']
        ),
    ]);

    $connector = Anaf::taxPayer()->withMockClient($mockClient);
    $response = $connector->vatStatuses(cifs: [29930516]);

    expect($response['success'])->toBeFalse()
        ->and($response['error'])->toBe('Serviciu indisponibil');
});
