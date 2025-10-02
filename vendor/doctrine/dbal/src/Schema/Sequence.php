<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Schema\Visitor\Visitor;
use Doctrine\Deprecations\Deprecation;

use function count;
use function sprintf;

/**
 * Sequence structure.
 */
class Sequence extends AbstractAsset
{
    /** @var int */
    protected $allocationSize = 1;

    /** @var int */
    protected $initialValue = 1;

    /** @var int|null */
    protected $cache;

    /**
     * @param string   $name
     * @param int      $allocationSize
     * @param int      $initialValue
     * @param int|null $cache
     */
    public function __construct($name, $allocationSize = 1, $initialValue = 1, $cache = null)
    {
        $this->_setName($name);
        $this->setAllocationSize($allocationSize);
        $this->setInitialValue($initialValue);
        $this->cache = $cache;
    }

    /** @return int */
    public function getAllocationSize()
    {
        return $this->allocationSize;
    }

    /** @return int */
    public function getInitialValue()
    {
        return $this->initialValue;
    }

    /** @return int|null */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param int $allocationSize
     *
     * @return Sequence
     */
    public function setAllocationSize($allocationSize)
    {
        if ($allocationSize > 0) {
            $this->allocationSize = $allocationSize;
        } else {
            $this->allocationSize = 1;
        }

        return $this;
    }

    /**
     * @param int $initialValue
     *
     * @return Sequence
     */
    public function setInitialValue($initialValue)
    {
        if ($initialValue > 0) {
            $this->initialValue = $initialValue;
        } else {
            $this->initialValue = 1;
        }

        return $this;
    }

    /**
     * @param int $cache
     *
     * @return Sequence
     */
    public function setCache($cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Checks if this sequence is an autoincrement sequence for a given table.
     *
     * This is used inside the comparator to not report sequences as missing,
     * when the "from" schema implicitly creates the sequences.
     *
     * @return bool
     */
    public function isAutoIncrementsFor(Table $table)
    {
        $primaryKey = $table->getPrimaryKey();

        if ($primaryKey === null) {
            return false;
        }

        $pkColumns = $primaryKey->getColumns();

        if (count($pkColumns) !== 1) {
            return false;
        }

        $column = $table->getColumn($pkColumns[0]);

        if (! $column->getAutoincrement()) {
            return false;
        }

        $sequenceName      = $this->getShortestName($table->getNamespaceName());
        $tableName         = $table->getShortestName($table->getNamespaceName());
        $tableSequenceName = sprintf('%s_%s_seq', $tableName, $column->getShortestName($table->getNamespaceName()));

        return $tableSequenceName === $sequenceName;
    }

    /**
     * @deprecated
     *
     * @return void
     */
    public function visit(Visitor $visitor)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5435',
            'Sequence::visit() is deprecated.',
        );

        $visitor->acceptSequence($this);
    }
}
