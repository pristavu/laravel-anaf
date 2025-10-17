<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Requests\Efactura;

use Exception;
use Pristavu\Anaf\Exceptions\AnafException;
use Pristavu\Anaf\Responses\Efactura\MessageStatusErrorResponse;
use Pristavu\Anaf\Responses\Efactura\MessageStatusResponse;
use Pristavu\Anaf\Support\XmlToArray;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\RequestProperties\HasQuery;
use Throwable;

/**
 * Check the status of a previously uploaded e-invoice using its upload ID.
 *
 * @see https://mfinante.gov.ro/static/10/eFactura/staremesaj.html
 */
final class MessageStatusRequest extends Request
{
    use HasQuery;

    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $uploadId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/stareMesaj';
    }

    public function getRequestException(Response $response, ?Throwable $senderException): Throwable
    {
        return new AnafException(
            response: $response,
            message: $response->json('message') ?? $response->json('error') ?? 'Unknown error',
            code: $response->json('status') ?? $senderException?->getCode() ?? 0,
        );
    }

    public function createDtoFromResponse(Response $response): MessageStatusResponse|MessageStatusErrorResponse
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

            return MessageStatusErrorResponse::fromResponse($error);
        }

        $status = $data['@attributes']['stare'];
        $downloadId = (int) @$data['@attributes']['id_descarcare'];

        return MessageStatusResponse::fromResponse($status, $downloadId);
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/xml, application/json;q=0.9, */*;q=0.8',
        ];
    }

    protected function defaultQuery(): array
    {
        return [
            'id_incarcare' => $this->uploadId,
        ];
    }
}
