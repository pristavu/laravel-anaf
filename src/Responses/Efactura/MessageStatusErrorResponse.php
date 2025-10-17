<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Responses\Efactura;

use Pristavu\Anaf\Contracts\AnafResponse;

readonly class MessageStatusErrorResponse implements AnafResponse
{
    public function __construct(
        public bool $success,
        public string $error,
    ) {}

    public static function fromResponse(string $error): self
    {
        return new self(
            success: false,
            error: $error,
        );

    }
}
