<?php

namespace Doctrine\DBAL\Schema;

/**
 * Representation of a Database View.
 */
class View extends AbstractAsset
{
    /** @var string */
    private $sql;

    /**
     * @param string $name
     * @param string $sql
     */
    public function __construct($name, $sql)
    {
        $this->_setName($name);
        $this->sql = $sql;
    }

    /** @return string */
    public function getSql()
    {
        return $this->sql;
    }
}
