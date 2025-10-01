<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Exception;

use Doctrine\DBAL\Exception;

use function sprintf;

class DatabaseRequired extends Exception
{
    public static function new(string $methodName): self
    {
        return new self(sprintf('A database is required for the method: %s.', $methodName));
    }
}
