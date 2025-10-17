<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Connectors;

use Pristavu\Anaf\Concerns\SupportTaxPayer;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

final class TaxPayerConnector extends Connector
{
    use AlwaysThrowOnErrors;
    use SupportTaxPayer;

    private int $timeoutInSeconds;

    public function __construct()
    {

        $this->timeoutInSeconds = (int) config('anaf.request_timeout', 15);
    }

    public function setTimeout(int $timeoutInSeconds): self
    {
        $this->timeoutInSeconds = $timeoutInSeconds;

        return $this;
    }

    public function resolveBaseUrl(): string
    {
        return 'https://webservicesp.anaf.ro';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    protected function defaultConfig(): array
    {
        return [
            'timeout' => $this->timeoutInSeconds,
        ];
    }
}
