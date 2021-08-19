<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\IBMDB2\Exception;

use Doctrine\DBAL\Driver\AbstractException;

/**
 * @internal
 *
 * @psalm-immutable
 */
final class CannotWriteToTemporaryFile extends AbstractException
{
    /**
     * @psalm-param array{message: string}|null $error
     */
    public static function new(?array $error): self
    {
        $message = 'Could not write string to temporary file';

        if ($error !== null) {
            $message .= ': ' . $error['message'];
        }

        return new self($message);
    }
}
