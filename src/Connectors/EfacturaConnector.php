<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Connectors;

use Pristavu\Anaf\Concerns\SupportEfactura;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

final class EfacturaConnector extends Connector
{
    use AlwaysThrowOnErrors;
    use SupportEfactura;

    private bool $testMode;

    private int $timeoutInSeconds;

    public function __construct(
        private readonly string $accessToken,
    ) {
        $this->testMode = config('anaf.efactura.test_mode', false);
        $this->timeoutInSeconds = config('anaf.efactura.timeout', 15);
    }

    public function inTestMode(): self
    {
        $this->testMode = true;

        return $this;
    }

    public function inLiveMode(): self
    {
        $this->testMode = false;

        return $this;
    }

    public function setTimeout(int $timeoutInSeconds): self
    {
        $this->timeoutInSeconds = $timeoutInSeconds;

        return $this;
    }

    public function resolveBaseUrl(): string
    {
        return $this->testMode
            ? 'https://api.anaf.ro/test/FCTEL/rest'
            : 'https://api.anaf.ro/prod/FCTEL/rest';
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

    protected function defaultAuth(): ?TokenAuthenticator
    {
        return $this->accessToken !== '' && $this->accessToken !== '0' ? new TokenAuthenticator($this->accessToken) : null;
    }
}
