<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Requests\TaxPayer;

use InvalidArgumentException;
use Pristavu\Anaf\Exceptions\AnafException;
use Pristavu\Anaf\Support\Validate;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use Throwable;

/**
 * Check the VAT status of up to 100 Fiscal Identification Codes (CIFs) on a
 * given date in a single request — the batch size and the 1 request/second
 * rate are ANAF's documented limits.
 *
 * @see https://static.anaf.ro/static/10/Anaf/Informatii_R/Servicii_web/doc_WS_V9.txt
 */
class VatStatusBatchRequest extends Request implements HasBody
{
    use HasJsonBody;

    public const int MAX_CIFS = 100;

    protected Method $method = Method::POST;

    /**
     * The validated CIFs, normalized to bare integers.
     *
     * @var list<int>
     */
    private readonly array $cifs;

    /**
     * @param  list<int|string>  $cifs  Accepts the same shapes as Validate::cif (e.g. "RO29930516").
     */
    public function __construct(
        array $cifs,
        private readonly ?string $date,
    ) {
        if ($cifs === []) {
            throw new InvalidArgumentException('A batch must contain at least one CIF.');
        }

        if (count($cifs) > self::MAX_CIFS) {
            throw new InvalidArgumentException('A batch can contain at most 100 CIFs.');
        }

        $normalized = [];

        foreach ($cifs as $cif) {
            if (! Validate::cif($cif)) {
                throw new InvalidArgumentException('The provided CIF is invalid.');
            }

            $normalized[] = (int) preg_replace('/\D/', '', (string) $cif);
        }

        $this->cifs = $normalized;
    }

    public function resolveEndpoint(): string
    {
        return '/api/PlatitorTvaRest/v9/tva';
    }

    public function getRequestException(Response $response, ?Throwable $senderException): Throwable
    {
        return new AnafException(
            response: $response,
            message: $response->json('message') ?? $response->json('error') ?? $response->json('eroare') ?? 'Unknown error',
            code: $response->json('status') ?? $response->json('cod') ?? $senderException?->getCode() ?? 0,
        );
    }

    /**
     * @return array{success: bool, error?: string, found?: array<int, array<string, mixed>>, not_found?: array<int, int>}
     */
    public function createDtoFromResponse(Response $response): array
    {
        if ($response->json('eroare')) {
            return [
                'success' => false,
                'error' => $response->json('eroare'),
            ];
        }

        return [
            'success' => $response->status() === 200,
            'found' => $response->json('found', []),
            'not_found' => $response->json('notFound', []),
        ];
    }

    protected function defaultBody(): array
    {
        return array_map(
            fn (int $cif): array => ['cui' => $cif, 'data' => $this->date],
            $this->cifs,
        );
    }
}
