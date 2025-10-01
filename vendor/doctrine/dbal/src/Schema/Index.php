<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use InvalidArgumentException;

use function array_filter;
use function array_keys;
use function array_map;
use function array_search;
use function array_shift;
use function count;
use function strtolower;

class Index extends AbstractAsset implements Constraint
{
    /**
     * Asset identifier instances of the column names the index is associated with.
     * array($columnName => Identifier)
     *
     * @var Identifier[]
     */
    protected $_columns = [];

    /** @var bool */
    protected $_isUnique = false;

    /** @var bool */
    protected $_isPrimary = false;

    /**
     * Platform specific flags for indexes.
     * array($flagName => true)
     *
     * @var true[]
     */
    protected $_flags = [];

    /**
     * Platform specific options
     *
     * @todo $_flags should eventually be refactored into options
     * @var mixed[]
     */
    private array $options = [];

    /**
     * @param string   $name
     * @param string[] $columns
     * @param bool     $isUnique
     * @param bool     $isPrimary
     * @param string[] $flags
     * @param mixed[]  $options
     */
    public function __construct(
        $name,
        array $columns,
        $isUnique = false,
        $isPrimary = false,
        array $flags = [],
        array $options = []
    ) {
        $isUnique = $isUnique || $isPrimary;

        $this->_setName($name);
        $this->_isUnique  = $isUnique;
        $this->_isPrimary = $isPrimary;
        $this->options    = $options;

        foreach ($columns as $column) {
            $this->_addColumn($column);
        }

        foreach ($flags as $flag) {
            $this->addFlag($flag);
        }
    }

    /** @throws InvalidArgumentException */
    protected function _addColumn(string $column): void
    {
        $this->_columns[$column] = new Identifier($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getColumns()
    {
        return array_keys($this->_columns);
    }

    /**
     * {@inheritDoc}
     */
    public function getQuotedColumns(AbstractPlatform $platform)
    {
        $subParts = $platform->supportsColumnLengthIndexes() && $this->hasOption('lengths')
            ? $this->getOption('lengths') : [];

        $columns = [];

        foreach ($this->_columns as $column) {
            $length = array_shift($subParts);

            $quotedColumn = $column->getQuotedName($platform);

            if ($length !== null) {
                $quotedColumn .= '(' . $length . ')';
            }

            $columns[] = $quotedColumn;
        }

        return $columns;
    }

    /** @return string[] */
    public function getUnquotedColumns()
    {
        return array_map([$this, 'trimQuotes'], $this->getColumns());
    }

    /**
     * Is the index neither unique nor primary key?
     *
     * @return bool
     */
    public function isSimpleIndex()
    {
        return ! $this->_isPrimary && ! $this->_isUnique;
    }

    /** @return bool */
    public function isUnique()
    {
        return $this->_isUnique;
    }

    /** @return bool */
    public function isPrimary()
    {
        return $this->_isPrimary;
    }

    /**
     * @param string $name
     * @param int    $pos
     *
     * @return bool
     */
    public function hasColumnAtPosition($name, $pos = 0)
    {
        $name         = $this->trimQuotes(strtolower($name));
        $indexColumns = array_map('strtolower', $this->getUnquotedColumns());

        return array_search($name, $indexColumns, true) === $pos;
    }

    /**
     * Checks if this index exactly spans the given column names in the correct order.
     *
     * @param string[] $columnNames
     *
     * @return bool
     */
    public function spansColumns(array $columnNames)
    {
        $columns         = $this->getColumns();
        $numberOfColumns = count($columns);
        $sameColumns     = true;

        for ($i = 0; $i < $numberOfColumns; $i++) {
            if (
                isset($columnNames[$i])
                && $this->trimQuotes(strtolower($columns[$i])) === $this->trimQuotes(strtolower($columnNames[$i]))
            ) {
                continue;
            }

            $sameColumns = false;
        }

        return $sameColumns;
    }

    /**
     * Keeping misspelled function name for backwards compatibility
     *
     * @deprecated Use {@see isFulfilledBy()} instead.
     *
     * @return bool
     */
    public function isFullfilledBy(Index $other)
    {
        return $this->isFulfilledBy($other);
    }

    /**
     * Checks if the other index already fulfills all the indexing and constraint needs of the current one.
     */
    public function isFulfilledBy(Index $other): bool
    {
        // allow the other index to be equally large only. It being larger is an option
        // but it creates a problem with scenarios of the kind PRIMARY KEY(foo,bar) UNIQUE(foo)
        if (count($other->getColumns()) !== count($this->getColumns())) {
            return false;
        }

        // Check if columns are the same, and even in the same order
        $sameColumns = $this->spansColumns($other->getColumns());

        if ($sameColumns) {
            if (! $this->samePartialIndex($other)) {
                return false;
            }

            if (! $this->hasSameColumnLengths($other)) {
                return false;
            }

            if (! $this->isUnique() && ! $this->isPrimary()) {
                // this is a special case: If the current key is neither primary or unique, any unique or
                // primary key will always have the same effect for the index and there cannot be any constraint
                // overlaps. This means a primary or unique index can always fulfill the requirements of just an
                // index that has no constraints.
                return true;
            }

            if ($other->isPrimary() !== $this->isPrimary()) {
                return false;
            }

            return $other->isUnique() === $this->isUnique();
        }

        return false;
    }

    /**
     * Detects if the other index is a non-unique, non primary index that can be overwritten by this one.
     *
     * @return bool
     */
    public function overrules(Index $other)
    {
        if ($other->isPrimary()) {
            return false;
        }

        if ($this->isSimpleIndex() && $other->isUnique()) {
            return false;
        }

        return $this->spansColumns($other->getColumns())
            && ($this->isPrimary() || $this->isUnique())
            && $this->samePartialIndex($other);
    }

    /**
     * Returns platform specific flags for indexes.
     *
     * @return string[]
     */
    public function getFlags()
    {
        return array_keys($this->_flags);
    }

    /**
     * Adds Flag for an index that translates to platform specific handling.
     *
     * @param string $flag
     *
     * @return Index
     *
     * @example $index->addFlag('CLUSTERED')
     */
    public function addFlag($flag)
    {
        $this->_flags[strtolower($flag)] = true;

        return $this;
    }

    /**
     * Does this index have a specific flag?
     *
     * @param string $flag
     *
     * @return bool
     */
    public function hasFlag($flag)
    {
        return isset($this->_flags[strtolower($flag)]);
    }

    /**
     * Removes a flag.
     *
     * @param string $flag
     *
     * @return void
     */
    public function removeFlag($flag)
    {
        unset($this->_flags[strtolower($flag)]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasOption($name)
    {
        return isset($this->options[strtolower($name)]);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getOption($name)
    {
        return $this->options[strtolower($name)];
    }

    /** @return mixed[] */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Return whether the two indexes have the same partial index
     */
    private function samePartialIndex(Index $other): bool
    {
        if (
            $this->hasOption('where')
            && $other->hasOption('where')
            && $this->getOption('where') === $other->getOption('where')
        ) {
            return true;
        }

        return ! $this->hasOption('where') && ! $other->hasOption('where');
    }

    /**
     * Returns whether the index has the same column lengths as the other
     */
    private function hasSameColumnLengths(self $other): bool
    {
        $filter = static function (?int $length): bool {
            return $length !== null;
        };

        return array_filter($this->options['lengths'] ?? [], $filter)
            === array_filter($other->options['lengths'] ?? [], $filter);
    }
}
