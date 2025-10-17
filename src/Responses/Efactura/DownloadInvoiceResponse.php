<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Responses\Efactura;

use Pristavu\Anaf\Contracts\AnafResponse;

class DownloadInvoiceResponse implements AnafResponse
{
    public function __construct(
        public bool $success,
        public bool $cached,
        public string $content,
    ) {}

}
