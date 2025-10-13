<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Requests\TaxPayer;

use Pristavu\Anaf\Exceptions\AnafException;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use Throwable;

/**
 * Check the VAT status of a specific Fiscal Identification Code (CIF) on a given date.
 *
 * @see https://static.anaf.ro/static/10/Anaf/Informatii_R/Servicii_web/doc_WS_V9.txt
 */
class VatStatusRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $cif,
        private readonly ?string $date,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/PlatitorTvaRest/v9/tva';
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
            'data' => $response->json('found.0', []),
        ];
    }

    protected function defaultBody(): array
    {
        return [
            [
                'cui' => $this->cif,
                'data' => $this->date,
            ],
        ];
    }
}
