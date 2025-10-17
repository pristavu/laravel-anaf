<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Requests\Efactura;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use JsonException;
use Pristavu\Anaf\Contracts\AnafResponse;
use Pristavu\Anaf\Exceptions\AnafException;
use Pristavu\Anaf\Responses\Efactura\DownloadInvoiceResponse;
use Pristavu\Anaf\Responses\Efactura\ErrorResponse;
use Saloon\CachePlugin\Contracts\Cacheable;
use Saloon\CachePlugin\Contracts\Driver;
use Saloon\CachePlugin\Drivers\LaravelCacheDriver;
use Saloon\CachePlugin\Traits\HasCaching;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Throwable;

/**
 * Download an e-invoice by its download ID.
 *
 * @see https://mfinante.gov.ro/static/10/eFactura/descarcare.html
 */
final class DownloadInvoiceRequest extends Request implements Cacheable
{
    use HasCaching;

    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $downloadId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/descarcare';
    }

    /**
     * @throws JsonException
     */
    public function getRequestException(Response $response, ?Throwable $senderException): Throwable
    {
        return new AnafException(
            response: $response,
            message: $response->json('message') ?? $response->json('error') ?? $senderException?->getMessage() ?? 'Request failed',
            code: $response->json('status') ?? $senderException?->getCode() ?? 0,
        );
    }

    /**
     * @throws JsonException
     */
    public function createDtoFromResponse(Response $response): AnafResponse
    {
        if ($response->header('Content-Type') === 'application/json' && $response->json('eroare')) {
            return ErrorResponse::fromResponse($response);
        }

        return DownloadInvoiceResponse::fromResponse($response);

    }

    public function resolveCacheDriver(): Driver
    {
        return new LaravelCacheDriver(Cache::store(config('anaf.efactura.cache.store', 'file')));
    }

    public function cacheExpiryInSeconds(): int
    {
        return (int) config('anaf.efactura.cache.ttl', 3600); // One Hour
    }

    protected function defaultQuery(): array
    {
        return ['id' => $this->downloadId];
    }

    protected function cacheKey(PendingRequest $pendingRequest): string
    {
        return 'einvoice:download:'.Str::slug(http_build_query($this->defaultQuery()));
    }
}
