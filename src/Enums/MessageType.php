<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Enums;

enum MessageType: string
{
    case ERROR = 'E';
    case SENT = 'T';
    case RECEIVED = 'P';
    case MESSAGE = 'R';
}
