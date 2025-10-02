<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\IBMDB2\Exception;

use Doctrine\DBAL\Driver\AbstractException;

/** @internal */
final class PrepareFailed extends AbstractException
{
    /** @param array{message: string}|null $error */
    public static function new(?array $error): self
    {
        if ($error === null) {
            return new self('Unknown error');
        }

        return new self($error['message']);
    }
}
