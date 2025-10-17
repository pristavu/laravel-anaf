<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Dto\Efactura;

use Saloon\Http\Response;

class Meta
{
    public function __construct(

        public int $total = 0,
        public int $per_page = 0,
        public int $current_page = 0,
        public int $last_page = 0,
    ) {}

    public static function fromResponse(Response $response): self
    {
        return new self(
            total: (int) $response->json('numar_total_inregistrari', 0),
            per_page: (int) $response->json('numar_total_inregistrari_per_pagina', 0),
            current_page: (int) $response->json('index_pagina_curenta', 0),
            last_page: (int) $response->json('numar_total_pagini', 0),

        );
    }
}
