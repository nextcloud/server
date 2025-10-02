<?php

namespace Doctrine\DBAL;

/**
 * Contains portable column case conversions.
 */
final class ColumnCase
{
    /**
     * Convert column names to upper case.
     */
    public const UPPER = 1;

    /**
     * Convert column names to lower case.
     */
    public const LOWER = 2;

    /**
     * This class cannot be instantiated.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
