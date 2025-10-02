<?php

namespace Doctrine\DBAL\Exception;

use InvalidArgumentException;

class MalformedDsnException extends InvalidArgumentException
{
    public static function new(): self
    {
        return new self('Malformed database connection URL');
    }
}
