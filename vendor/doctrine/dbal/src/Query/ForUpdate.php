<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Query;

/** @internal */
final class ForUpdate
{
    private int $conflictResolutionMode;

    public function __construct(int $conflictResolutionMode)
    {
        $this->conflictResolutionMode = $conflictResolutionMode;
    }

    public function getConflictResolutionMode(): int
    {
        return $this->conflictResolutionMode;
    }
}
