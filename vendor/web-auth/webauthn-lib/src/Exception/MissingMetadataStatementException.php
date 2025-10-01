<?php

declare(strict_types=1);

namespace Webauthn\Exception;

use Throwable;

/**
 * @final
 */
class MissingMetadataStatementException extends MetadataStatementException
{
    public function __construct(
        public readonly string $aaguid,
        string $message,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $previous);
    }

    public static function create(
        string $aaguid,
        string $message = 'The Metadata Statement is missing',
        ?Throwable $previous = null
    ): self {
        return new self($aaguid, $message, $previous);
    }
}
