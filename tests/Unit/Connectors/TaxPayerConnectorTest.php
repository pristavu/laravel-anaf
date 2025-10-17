<?php

declare(strict_types=1);

use Pristavu\Anaf\Connectors\TaxPayerConnector;

it('can be instantiated with valid cif', function (): void {

    $connector = Pristavu\Anaf\Facades\Anaf::taxPayer();

    expect($connector)->toBeInstanceOf(TaxPayerConnector::class);
});
