<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Responses\Efactura;

use Pristavu\Anaf\Contracts\AnafResponse;
use Saloon\Http\Response;

readonly class ConvertInvoiceErrorResponse implements AnafResponse
{
    public function __construct(
        public bool $success,
        public array $errors,
        public string $trace_id,
    ) {}

    public static function fromResponse(Response $response): self
    {
        return new self(
            success: false,
            errors: $response->json('Messages', []),
            trace_id: $response->json('trace_id', ''),
        );

    }
}
