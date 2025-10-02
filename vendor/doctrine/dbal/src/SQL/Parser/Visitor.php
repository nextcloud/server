<?php

namespace Doctrine\DBAL\SQL\Parser;

/**
 * SQL parser visitor
 *
 * @internal
 */
interface Visitor
{
    /**
     * Accepts an SQL fragment containing a positional parameter
     */
    public function acceptPositionalParameter(string $sql): void;

    /**
     * Accepts an SQL fragment containing a named parameter
     */
    public function acceptNamedParameter(string $sql): void;

    /**
     * Accepts other SQL fragments
     */
    public function acceptOther(string $sql): void;
}
