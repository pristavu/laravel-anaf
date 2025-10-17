<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Dto\Efactura;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Pristavu\Anaf\Enums\MessageType;
use Saloon\Http\Response;

readonly class Message
{
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

    public static function collect(Response $response): Collection
    {
        return collect($response->json('mesaje', []))
            ->map(fn (array $item): Message => self::fromResponse($item));
    }
}
