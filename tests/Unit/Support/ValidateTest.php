<?php

declare(strict_types=1);

test('cif validation works as expected', function ($cif, $expected): void {

    $result = Pristavu\Anaf\Support\Validate::cif($cif);
    $this->assertSame($expected, $result);

})->with([
    'valid cif' => [29930516, true],
    'invalid cif - too short' => [1234567, false],
    'invalid cif - too long' => [1234567890, false],
    'invalid cif - non-numeric' => ['ABCDEFGH', false],
    'invalid cif - negative number' => [-12345678, false],
    'invalid cif - zero' => [0, false],
]);
