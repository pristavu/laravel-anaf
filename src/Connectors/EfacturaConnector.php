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

    private bool $disableCaching;

    private bool $invalidateCache = false;

    public function __construct(
        private readonly string $accessToken,
    ) {
        $this->testMode = config('anaf.efactura.test_mode', false);
        $this->timeoutInSeconds = (int) config('anaf.request_timeout', 15);
        $this->disableCaching = config('anaf.efactura.cache.enabled', true) === false;
    }

    /**
     * Invalidate the cache for the next request.
     */
    public function invalidateCache(): self
    {

        $this->invalidateCache = true;

        return $this;
    }

    /**
     * Disable caching for the next request.
     */
    public function disableCaching(): self
    {

        $this->disableCaching = true;

        return $this;

    }

    /**
     * Enable test mode for the connector.
     * This will switch the base URL to the test environment.
     *
     * @return $this
     */
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
        return blank($this->accessToken) ? null : new TokenAuthenticator($this->accessToken);
    }
}
