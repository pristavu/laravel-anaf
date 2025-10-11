<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Exceptions;

use Exception;
use Saloon\Http\Response;

class AnafException extends Exception
{
    public function __construct(public ?Response $response, string $message, int $code)
    {
        parent::__construct($message, $code);
    }
}
