<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Responses\Efactura;

use Illuminate\Support\Collection;
use Pristavu\Anaf\Contracts\AnafResponse;

readonly class MessagesResponse implements AnafResponse
{
    public function __construct(
        public bool $success,
        public string $hash,
        public Collection $messages,
    ) {}

}
