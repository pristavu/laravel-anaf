<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Requests\Efactura;

use Pristavu\Anaf\Exceptions\AnafException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Throwable;

/**
 * https://mfinante.gov.ro/static/10/eFactura/descarcare.html
 */
final class DownloadInvoiceRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $downloadId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/descarcare';
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

        if ($response->header('Content-Type') === 'application/json' && $response->json('eroare')) {
            return [
                'success' => false,
                'error' => $response->json('eroare'),
            ];
        }

        return [
            'success' => $response->status() === 200,
            'content' => $response->body(),
        ];
    }

    protected function defaultQuery(): array
    {
        return ['id' => $this->downloadId];
    }
}
