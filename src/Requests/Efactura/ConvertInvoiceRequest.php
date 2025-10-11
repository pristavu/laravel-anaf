<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Requests\Efactura;

use Pristavu\Anaf\Enums\DocumentStandard;
use Pristavu\Anaf\Exceptions\AnafException;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasXmlBody;
use Throwable;

/**
 * https://mfinante.gov.ro/static/10/eFactura/upload.html
 */
final class ConvertInvoiceRequest extends Request implements HasBody
{
    use HasXmlBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $xml,
        private readonly ?DocumentStandard $standard = DocumentStandard::FACT1,
        private readonly bool $withoutValidation = false,
    ) {
        //
    }

    public function resolveEndpoint(): string
    {
        return '/transformare/'.$this->standard->value.($this->withoutValidation ? '/DA' : '');
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

        if ($response->header('Content-Type') === 'application/json' && $response->json('stare') === 'nok') {
            return [
                'success' => false,
                ...($response->json('Messages') ? ['errors' => $response->json('Messages')] : []),
                'trace_id' => $response->json('trace_id'),
            ];
        }

        return [
            'success' => $response->status() === 200,
            'content' => $response->body(),
        ];
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'text/plain',
            'Accept' => '*/*',
        ];
    }

    protected function defaultBody(): string
    {
        if (file_exists($this->xml)) {
            return file_get_contents($this->xml);
        }

        return $this->xml;
    }
}
