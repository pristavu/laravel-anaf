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
 * Validate an e-invoice XML file or content against the specified standard.
 *
 * @see https://mfinante.gov.ro/static/10/eFactura/upload.html
 */
final class ValidateInvoiceRequest extends Request implements HasBody
{
    use HasXmlBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $xml,
        private readonly ?DocumentStandard $standard = DocumentStandard::FACT1,
    ) {
        //
    }

    public function resolveEndpoint(): string
    {
        return '/validare/'.$this->standard->value;
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
        return [
            'success' => $response->status() === 200,
            'is_valid' => $response->json('stare') === 'ok',
            ...($response->json('Messages') ? ['errors' => $response->json('Messages')] : []),
            'trace_id' => $response->json('trace_id'),
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
