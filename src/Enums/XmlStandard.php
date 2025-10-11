<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Enums;

enum XmlStandard: string
{
    case UBL = 'UBL';
    case CN = 'CN';
    case CII = 'CII';
    case RASP = 'RASP';
}
