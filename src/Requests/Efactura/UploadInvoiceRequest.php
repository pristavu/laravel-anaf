<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Requests\Efactura;

use Exception;
use Pristavu\Anaf\Enums\XmlStandard;
use Pristavu\Anaf\Exceptions\AnafException;
use Pristavu\Anaf\Responses\Efactura\UploadInvoiceErrorResponse;
use Pristavu\Anaf\Responses\Efactura\UploadInvoiceResponse;
use Pristavu\Anaf\Support\XmlToArray;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasXmlBody;
use Throwable;

/**
 * Upload an e-invoice XML file or content to the ANAF system.
 *
 * @see https://mfinante.gov.ro/static/10/eFactura/upload.html
 */
final class UploadInvoiceRequest extends Request implements HasBody
{
    use HasXmlBody;

    protected Method $method = Method::POST;

    private bool $b2c = false;

    public function __construct(
        private readonly int $cif,
        private readonly string $xml,
        private readonly ?XmlStandard $standard = XmlStandard::UBL,
        private readonly ?bool $isExternal = false,
        private readonly ?bool $isSelfInvoice = false,
        private readonly ?bool $isLegalEnforcement = false,

    ) {
        //
    }

    public function resolveEndpoint(): string
    {
        return $this->b2c ? '/uploadb2c' : '/upload';
    }

    public function setB2C(bool $b2c): self
    {
        $this->b2c = $b2c;

        return $this;
    }

    public function getRequestException(Response $response, ?Throwable $senderException): Throwable
    {
        return new AnafException(
            response: $response,
            message: $response->json('message') ?? $response->json('error') ?? 'Unknown error',
            code: $response->json('status') ?? $senderException?->getCode() ?? 0,
        );
    }

    /**
     * @throws AnafException
     */
    public function createDtoFromResponse(Response $response): UploadInvoiceResponse|UploadInvoiceErrorResponse
    {

        try {
            $data = XmlToArray::convert($response->body());
        } catch (Exception $e) {
            throw new AnafException(
                response: $response,
                message: 'Invalid XML response',
                code: $e->getCode(),

            );
        }

        $isError = isset($data['Errors']['@attributes']['errorMessage']);
        if ($isError) {
            $error = $data['Errors']['@attributes']['errorMessage'] ?? 'Unknown error';

            return UploadInvoiceErrorResponse::fromResponse($error);
        }

        $uploadId = (int) $data['@attributes']['index_incarcare'];

        return UploadInvoiceResponse::fromResponse($uploadId);

    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/xml',
            'Accept' => '*/*',
        ];
    }

    protected function defaultQuery(): array
    {
        return [
            'cif' => $this->cif,
            'standard' => $this->standard->value,
            ...($this->isExternal === true ? ['extern' => 'DA'] : []),
            ...($this->isSelfInvoice === true ? ['autofactura' => 'DA'] : []),
            ...($this->isLegalEnforcement === true ? ['executare' => 'DA'] : []),
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
