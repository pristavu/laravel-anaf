<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Requests\Efactura;

use Carbon\CarbonPeriod;
use Pristavu\Anaf\Dto\Efactura\Message;
use Pristavu\Anaf\Enums\MessageType;
use Pristavu\Anaf\Exceptions\AnafException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Throwable;

/**
 * Retrieve paginated e-invoice messages for a specific Fiscal Identification Code within a given date range.
 *
 * @see https://mfinante.gov.ro/static/10/eFactura/listamesaje.html#/EFacturaListaMesaje/getPaginatie
 */
final class MessagesPaginatedRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $cif,
        private readonly CarbonPeriod $period,
        private readonly int $page = 1,
        private readonly ?MessageType $type = null
    ) {}

    public function resolveEndpoint(): string
    {
        return '/listaMesajePaginatieFactura';
    }

    public function getRequestException(Response $response, ?Throwable $senderException): Throwable
    {
        return new AnafException(
            response: $response,
            message: $response->json('message') ?? $response->json('error') ?? 'Unknown error',
            code: $response->json('status') ?? $senderException?->getCode() ?? 0,
        );
    }

    public function createDtoFromResponse(Response $response): array
    {
        if ($response->json('eroare')) {
            return [
                'success' => false,
                'error' => $response->json('eroare'),
            ];
        }

        return [
            'success' => $response->status() === 200,
            'hash' => $response->json('serial'),
            'messages' => Message::collect($response),
            'meta' => [
                'total' => $response->json('numar_total_inregistrari') ?? 0,
                'per_page' => $response->json('numar_total_inregistrari_per_pagina') ?? 0,
                'current_page' => $response->json('index_pagina_curenta') ?? 0,
                'last_page' => $response->json('numar_total_pagini') ?? 0,
            ],
        ];
    }

    protected function defaultQuery(): array
    {
        $query = [
            'cif' => $this->cif,
            'startTime' => $this->period->getStartDate()->getTimestamp() * 1000,
            'endTime' => $this->period->getEndDate()->getTimestamp() * 1000,
            'pagina' => $this->page,
        ];

        if ($this->type instanceof MessageType) {
            $query['filtru'] = $this->type->value;
        }

        return $query;
    }
}
