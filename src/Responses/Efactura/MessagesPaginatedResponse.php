<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Responses\Efactura;

use Illuminate\Support\Collection;
use Pristavu\Anaf\Contracts\AnafResponse;
use Pristavu\Anaf\Dto\Efactura\Message;
use Pristavu\Anaf\Dto\Efactura\Meta;
use Saloon\Http\Response;

readonly class MessagesPaginatedResponse implements AnafResponse
{
    public function __construct(
        public bool $success,
        public string $hash,
        public Collection $messages,
        public Meta $meta

    ) {}

    public static function fromResponse(Response $response): self
    {

        return new self(
            success: $response->status() === 200,
            hash: (string) $response->json('serial', ''),
            messages: Message::collect($response),
            meta: Meta::fromResponse($response),
        );
    }
}
