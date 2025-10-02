<?php

declare(strict_types=1);

namespace Doctrine\DBAL\SQL\Parser\Exception;

use Doctrine\DBAL\SQL\Parser\Exception;
use RuntimeException;

use function preg_last_error;
use function preg_last_error_msg;

class RegularExpressionError extends RuntimeException implements Exception
{
    public static function new(): self
    {
        return new self(preg_last_error_msg(), preg_last_error());
    }
}
