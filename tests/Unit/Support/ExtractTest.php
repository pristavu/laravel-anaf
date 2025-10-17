<?php

declare(strict_types=1);

use Pristavu\Anaf\Support\Extract;

it('can extract files from zip archive inputs', function ($archive): void {

    $extract = Extract::from($archive);

    expect($extract->xmlInvoice())->toBeString()
        ->and($extract->signature())->toBeString()
        ->and($extract->dtoInvoice())->toBeInstanceOf(Einvoicing\Invoice::class)
        ->and($extract->toArray())->toBeArray();

})->with([
    'path' => __DIR__.'/../../Fixtures/Efactura/123456789.zip',
    'content' => file_get_contents(__DIR__.'/../../Fixtures/Efactura/123456789.zip'),
    'base64' => base64_encode(file_get_contents(__DIR__.'/../../Fixtures/Efactura/123456789.zip')),
]);

it('throws exception for invalid zip archive', function ($archive): void {
    Extract::from($archive);
})->with([
    'not-valid-zip' => 'not-a-valid-zip-archive',
    'not-valid-base64' => base64_encode('not-a-valid-zip-archive'),
    'corrupted' => __DIR__.'/../../Fixtures/Efactura/corrupted.zip',
])
    ->throws(RuntimeException::class, 'Archive must be a readable file path, raw ZIP bytes, or base64-encoded ZIP.');
