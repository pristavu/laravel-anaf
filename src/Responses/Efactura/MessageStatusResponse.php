<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Responses\Efactura;

use Pristavu\Anaf\Contracts\AnafResponse;

readonly class MessageStatusResponse implements AnafResponse
{
    public function __construct(
        public bool $success,
        public string $status,
        public int $download_id,
    ) {}

    public static function fromResponse(string $status, int $downloadId): self
    {
        return new self(
            success: true,
            status : $status,
            download_id: $downloadId,
        );
    }
}
