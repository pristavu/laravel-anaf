<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Support;

class Validate
{
    /**
     * Validate CIF (Fiscal Identification Code).
     *
     * @param  int|string  $cif  The CIF to validate.
     * @return bool True if the CIF is valid, false otherwise.
     */
    public static function cif(int|string $cif): bool
    {
        $cif = (string) preg_replace('/\D/', '', (string) $cif);

        if (mb_strlen($cif) < 2 || mb_strlen($cif) > 10 || ! ctype_digit($cif)) {
            return false;
        }

        $weights = [7, 5, 3, 2, 1, 7, 5, 3, 2];
        $length = mb_strlen($cif);
        $sum = 0;

        $offset = 10 - $length;
        for ($i = 0; $i < $length - 1; $i++) {
            $sum += (int) $cif[$i] * $weights[$offset + $i];
        }

        $remainder = ($sum * 10) % 11;
        $checkDigit = ($remainder === 10) ? 0 : $remainder;

        return (int) $cif[$length - 1] === $checkDigit;
    }
}
