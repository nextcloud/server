<?php

namespace Doctrine\DBAL;

/**
 * Legacy Class that keeps BC for using the legacy APIs fetch()/fetchAll().
 */
class FetchMode
{
    /** @link PDO::FETCH_ASSOC */
    public const ASSOCIATIVE = 2;
    /** @link PDO::FETCH_NUM */
    public const NUMERIC = 3;
    /** @link PDO::FETCH_COLUMN */
    public const COLUMN = 7;
}
