<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Responses\Efactura;

use Pristavu\Anaf\Contracts\AnafResponse;
use Saloon\Http\Response;

readonly class ErrorResponse implements AnafResponse
{
    public function __construct(
        public bool $success,
        public string $error,
    ) {}

    public static function fromResponse(Response $response): self
    {
        return new self(
            success: false,
            error: $response->json('eroare', 'Unknown error occurred'),
        );

    }
}
