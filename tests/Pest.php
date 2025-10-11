<?php

declare(strict_types=1);

use Pristavu\Anaf\Requests\Efactura\MessagesRequest;
use Pristavu\Anaf\Requests\Efactura\MessageStatusRequest;
use Pristavu\Anaf\Tests\TestCase;
use Saloon\Config;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\MockConfig;

uses(TestCase::class)->in(__DIR__);

MockConfig::setFixturePath(path: 'tests/Fixtures');
Config::preventStrayRequests();
MockConfig::throwOnMissingFixtures();

MockClient::global(mockData: [
    MessagesRequest::class => MockResponse::fixture(name: 'Efactura/MessagesResponse'),
    MessageStatusRequest::class => MockResponse::fixture(name: 'Efactura/MessageStatusResponse'),
]);
