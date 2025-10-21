<?php

declare(strict_types=1);

use Pristavu\Anaf\Anaf;

if (! function_exists('anaf')) {
    /**
     * Create a new pending anaf.
     */
    function anaf(): Anaf
    {
        return new Anaf();
    }
}
