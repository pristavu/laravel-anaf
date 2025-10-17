<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Requests\Efactura;

use Pristavu\Anaf\Contracts\AnafResponse;
use Pristavu\Anaf\Dto\Efactura\Message;
use Pristavu\Anaf\Enums\MessageType;
use Pristavu\Anaf\Exceptions\AnafException;
use Pristavu\Anaf\Responses\Efactura\ErrorResponse;
use Pristavu\Anaf\Responses\Efactura\MessagesResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\RequestProperties\HasQuery;
use Throwable;

/**
 * Retrieve e-invoice messages for a specific Fiscal Identification Code within the last given number of days.
 *
 * @see https://mfinante.gov.ro/static/10/eFactura/listamesaje.html#/EFacturaListaMesaje/getListaMesaje
 */
final class MessagesRequest extends Request
{
    use HasQuery;

    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $cif,
        private readonly ?int $days = 60,
        private readonly ?MessageType $type = null
    ) {}

    public function resolveEndpoint(): string
    {
        return '/listaMesajeFactura';
    }

    public function getRequestException(Response $response, ?Throwable $senderException): Throwable
    {
        return new AnafException(
            response: $response,
            message: $response->json('message') ?? $response->json('error') ?? 'Unknown error',
            code: $response->json('status') ?? $senderException?->getCode() ?? 0,
        );
    }

    public function createDtoFromResponse(Response $response): AnafResponse
    {
        if ($response->json('eroare')) {
            return new ErrorResponse(
                success: false,
                error: $response->json('eroare'),
            );
        }

        return new MessagesResponse(
            success: $response->status() === 200,
            hash: $response->json('serial'),
            messages: Message::collect($response),
        );
    }

    protected function defaultQuery(): array
    {
        $query = [
            'cif' => $this->cif,
            'zile' => $this->days,
        ];

        if ($this->type instanceof MessageType) {
            $query['filtru'] = $this->type;
        }

        return $query;
    }
}
