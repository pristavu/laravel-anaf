<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Pristavu\Anaf\Anaf
 */
final class Anaf extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Pristavu\Anaf\Anaf::class;
    }
}
