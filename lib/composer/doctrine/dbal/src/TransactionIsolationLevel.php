<?php

declare(strict_types=1);

namespace Doctrine\DBAL;

final class TransactionIsolationLevel
{
    /**
     * Transaction isolation level READ UNCOMMITTED.
     */
    public const READ_UNCOMMITTED = 1;

    /**
     * Transaction isolation level READ COMMITTED.
     */
    public const READ_COMMITTED = 2;

    /**
     * Transaction isolation level REPEATABLE READ.
     */
    public const REPEATABLE_READ = 3;

    /**
     * Transaction isolation level SERIALIZABLE.
     */
    public const SERIALIZABLE = 4;

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
