<?php

namespace Doctrine\DBAL;

/**
 * Contains statement parameter types.
 */
final class ParameterType
{
    /**
     * Represents the SQL NULL data type.
     */
    public const NULL = 0;

    /**
     * Represents the SQL INTEGER data type.
     */
    public const INTEGER = 1;

    /**
     * Represents the SQL CHAR, VARCHAR, or other string data type.
     *
     * @see \PDO::PARAM_STR
     */
    public const STRING = 2;

    /**
     * Represents the SQL large object data type.
     */
    public const LARGE_OBJECT = 3;

    /**
     * Represents a boolean data type.
     *
     * @see \PDO::PARAM_BOOL
     */
    public const BOOLEAN = 5;

    /**
     * Represents a binary string data type.
     */
    public const BINARY = 16;

    /**
     * Represents an ASCII string data type
     */
    public const ASCII = 17;

    /**
     * This class cannot be instantiated.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
