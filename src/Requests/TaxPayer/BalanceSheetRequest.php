<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Requests\TaxPayer;

use InvalidArgumentException;
use Pristavu\Anaf\Exceptions\AnafException;
use Pristavu\Anaf\Support\Validate;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Throwable;

/**
 *  Retrieve the balance sheet for a specific Fiscal Identification Code (CIF) and year.
 *
 * @see https://static.anaf.ro/static/10/Anaf/Informatii_R/doc_WS_Bilant_V1.txt
 */
class BalanceSheetRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $cif,
        private readonly int $year,
    ) {

        if (! Validate::cif($this->cif)) {
            throw new InvalidArgumentException('The provided CIF is invalid.');
        }

    }

    public function resolveEndpoint(): string
    {
        return '/bilant';
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
            'data' => $response->json(),
        ];
    }

    protected function defaultQuery(): array
    {
        return [
            'cui' => $this->cif,
            'an' => $this->year,
        ];
    }
}
