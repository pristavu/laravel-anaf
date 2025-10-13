<?php

declare(strict_types=1);

use Pristavu\Anaf\Connectors\TaxPayerConnector;

it('throws exception on invalid cif', function (): void {
    new TaxPayerConnector(123);

})->throws(InvalidArgumentException::class, $message = 'The provided CIF is invalid.');

it('can be instantiated with valid cif', function (): void {

    $connector = Pristavu\Anaf\Facades\Anaf::taxPayer(29930516);

    expect($connector)->toBeInstanceOf(TaxPayerConnector::class);
});
