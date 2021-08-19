<?php

namespace Doctrine\DBAL\Schema;

/**
 * Configuration for a Schema.
 */
class SchemaConfig
{
    /** @var bool */
    protected $hasExplicitForeignKeyIndexes = false;

    /** @var int */
    protected $maxIdentifierLength = 63;

    /** @var string */
    protected $name;

    /** @var mixed[] */
    protected $defaultTableOptions = [];

    /**
     * @return bool
     */
    public function hasExplicitForeignKeyIndexes()
    {
        return $this->hasExplicitForeignKeyIndexes;
    }

    /**
     * @param bool $flag
     *
     * @return void
     */
    public function setExplicitForeignKeyIndexes($flag)
    {
        $this->hasExplicitForeignKeyIndexes = (bool) $flag;
    }

    /**
     * @param int $length
     *
     * @return void
     */
    public function setMaxIdentifierLength($length)
    {
        $this->maxIdentifierLength = (int) $length;
    }

    /**
     * @return int
     */
    public function getMaxIdentifierLength()
    {
        return $this->maxIdentifierLength;
    }

    /**
     * Gets the default namespace of schema objects.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the default namespace name of schema objects.
     *
     * @param string $name The value to set.
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the default options that are passed to Table instances created with
     * Schema#createTable().
     *
     * @return mixed[]
     */
    public function getDefaultTableOptions()
    {
        return $this->defaultTableOptions;
    }

    /**
     * @param mixed[] $defaultTableOptions
     *
     * @return void
     */
    public function setDefaultTableOptions(array $defaultTableOptions)
    {
        $this->defaultTableOptions = $defaultTableOptions;
    }
}
