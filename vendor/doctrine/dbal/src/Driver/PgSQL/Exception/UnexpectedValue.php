<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\PgSQL\Exception;

use Doctrine\DBAL\Driver\Exception;
use UnexpectedValueException;

use function sprintf;

final class UnexpectedValue extends UnexpectedValueException implements Exception
{
    public static function new(string $value, string $type): self
    {
        return new self(sprintf(
            'Unexpected value "%s" of type "%s" returned by Postgres',
            $value,
            $type,
        ));
    }

    /** @return null */
    public function getSQLState()
    {
        return null;
    }
}
