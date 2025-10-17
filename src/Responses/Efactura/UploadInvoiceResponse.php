<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Responses\Efactura;

use Pristavu\Anaf\Contracts\AnafResponse;

readonly class UploadInvoiceResponse implements AnafResponse
{
    public function __construct(
        public bool $success,
        public int $upload_id,
    ) {}

    public static function fromResponse(int $uploadId): self
    {
        return new self(
            success: true,
            upload_id: $uploadId,
        );
    }
}
