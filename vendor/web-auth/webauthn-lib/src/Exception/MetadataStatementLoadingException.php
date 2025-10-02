<?php

declare(strict_types=1);

namespace Webauthn\Exception;

use Throwable;

/**
 * @final
 */
class MetadataStatementLoadingException extends MetadataStatementException
{
    public static function create(
        string $message = 'Unable to load the metadata statement',
        ?Throwable $previous = null
    ): self {
        return new self($message, $previous);
    }
}
