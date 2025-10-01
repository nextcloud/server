<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Statement;

use JsonSerializable;
use Webauthn\Exception\MetadataStatementLoadingException;

abstract class AbstractDescriptor implements JsonSerializable
{
    public function __construct(
        public readonly ?int $maxRetries = null,
        public readonly ?int $blockSlowdown = null
    ) {
        $maxRetries >= 0 || throw MetadataStatementLoadingException::create(
            'Invalid data. The value of "maxRetries" must be a positive integer'
        );
        $blockSlowdown >= 0 || throw MetadataStatementLoadingException::create(
            'Invalid data. The value of "blockSlowdown" must be a positive integer'
        );
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getMaxRetries(): ?int
    {
        return $this->maxRetries;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getBlockSlowdown(): ?int
    {
        return $this->blockSlowdown;
    }
}
