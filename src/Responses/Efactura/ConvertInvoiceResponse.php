<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Responses\Efactura;

use Pristavu\Anaf\Contracts\AnafResponse;
use Saloon\Http\Response;

readonly class ConvertInvoiceResponse implements AnafResponse
{
    public function __construct(
        public bool $success,
        public string $content,
    ) {}

    public static function fromResponse(Response $response): self
    {
        return new self(
            success: $response->status() === 200,
            content: $response->body(),
        );
    }
}
