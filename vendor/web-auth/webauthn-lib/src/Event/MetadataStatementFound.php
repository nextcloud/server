<?php

declare(strict_types=1);

namespace Webauthn\Event;

use Webauthn\MetadataService\Statement\MetadataStatement;

/**
 * @final
 */
class MetadataStatementFound implements WebauthnEvent
{
    public function __construct(
        public readonly MetadataStatement $metadataStatement
    ) {
    }

    public static function create(MetadataStatement $metadataStatement): self
    {
        return new self($metadataStatement);
    }
}
