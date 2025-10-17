<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Dto\Efactura;

use Illuminate\Support\Carbon;
use Pristavu\Anaf\Enums\MessageType;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Http\Response;
use Saloon\Traits\Responses\HasResponse;

class Message implements WithResponse
{
    use HasResponse;

    public function __construct(
        public int $cif,
        public int $upload_id,
        public int $download_id,
        public Carbon $created_at,
        public MessageType $type,
        public string $description,
    ) {}

    /**
     * @param  array{data_creare: string, cif: string, id_solicitare: string, detalii: string, tip: string, id: string}  $data
     */
    public static function fromResponse(array $data): self
    {

        return new self(
            cif: (int) $data['cif'],
            upload_id: (int) $data['id_solicitare'],
            download_id: (int) $data['id'],
            created_at: Carbon::createFromFormat('YmdHi', $data['data_creare']),
            type: match ($data['tip']) {
                'FACTURA TRIMISA' => MessageType::SENT,
                'FACTURA PRIMITA' => MessageType::RECEIVED,
                'ERORI FACTURA' => MessageType::ERROR,
                default => MessageType::MESSAGE,
            },
            description: $data['detalii'],

        );
    }

    public static function collect(Response $response): array
    {
        return array_map(
            fn (array $item): Message => self::fromResponse($item),
            $response->json('mesaje', [])
        );
    }
}
