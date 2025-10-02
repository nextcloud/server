<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;

use function array_keys;
use function array_map;
use function strrpos;
use function strtolower;
use function strtoupper;
use function substr;

/**
 * An abstraction class for a foreign key constraint.
 */
class ForeignKeyConstraint extends AbstractAsset implements Constraint
{
    /**
     * Instance of the referencing table the foreign key constraint is associated with.
     *
     * @var Table
     */
    protected $_localTable;

    /**
     * Asset identifier instances of the referencing table column names the foreign key constraint is associated with.
     * array($columnName => Identifier)
     *
     * @var Identifier[]
     */
    protected $_localColumnNames;

    /**
     * Table or asset identifier instance of the referenced table name the foreign key constraint is associated with.
     *
     * @var Table|Identifier
     */
    protected $_foreignTableName;

    /**
     * Asset identifier instances of the referenced table column names the foreign key constraint is associated with.
     * array($columnName => Identifier)
     *
     * @var Identifier[]
     */
    protected $_foreignColumnNames;

    /**
     * Options associated with the foreign key constraint.
     *
     * @var mixed[]
     */
    protected $_options;

    /**
     * Initializes the foreign key constraint.
     *
     * @param string[]     $localColumnNames   Names of the referencing table columns.
     * @param Table|string $foreignTableName   Referenced table.
     * @param string[]     $foreignColumnNames Names of the referenced table columns.
     * @param string|null  $name               Name of the foreign key constraint.
     * @param mixed[]      $options            Options associated with the foreign key constraint.
     */
    public function __construct(
        array $localColumnNames,
        $foreignTableName,
        array $foreignColumnNames,
        $name = null,
        array $options = []
    ) {
        if ($name !== null) {
            $this->_setName($name);
        }

        $this->_localColumnNames = $this->createIdentifierMap($localColumnNames);

        if ($foreignTableName instanceof Table) {
            $this->_foreignTableName = $foreignTableName;
        } else {
            $this->_foreignTableName = new Identifier($foreignTableName);
        }

        $this->_foreignColumnNames = $this->createIdentifierMap($foreignColumnNames);
        $this->_options            = $options;
    }

    /**
     * @param string[] $names
     *
     * @return Identifier[]
     */
    private function createIdentifierMap(array $names): array
    {
        $identifiers = [];

        foreach ($names as $name) {
            $identifiers[$name] = new Identifier($name);
        }

        return $identifiers;
    }

    /**
     * Returns the name of the referencing table
     * the foreign key constraint is associated with.
     *
     * @deprecated Use the table that contains the foreign key as part of its {@see Table::$_fkConstraints} instead.
     *
     * @return string
     */
    public function getLocalTableName()
    {
        return $this->_localTable->getName();
    }

    /**
     * Sets the Table instance of the referencing table
     * the foreign key constraint is associated with.
     *
     * @deprecated Use the table that contains the foreign key as part of its {@see Table::$_fkConstraints} instead.
     *
     * @param Table $table Instance of the referencing table.
     *
     * @return void
     */
    public function setLocalTable(Table $table)
    {
        $this->_localTable = $table;
    }

    /**
     * @deprecated Use the table that contains the foreign key as part of its {@see Table::$_fkConstraints} instead.
     *
     * @return Table
     */
    public function getLocalTable()
    {
        return $this->_localTable;
    }

    /**
     * Returns the names of the referencing table columns
     * the foreign key constraint is associated with.
     *
     * @return string[]
     */
    public function getLocalColumns()
    {
        return array_keys($this->_localColumnNames);
    }

    /**
     * Returns the quoted representation of the referencing table column names
     * the foreign key constraint is associated with.
     *
     * But only if they were defined with one or the referencing table column name
     * is a keyword reserved by the platform.
     * Otherwise the plain unquoted value as inserted is returned.
     *
     * @param AbstractPlatform $platform The platform to use for quotation.
     *
     * @return string[]
     */
    public function getQuotedLocalColumns(AbstractPlatform $platform)
    {
        $columns = [];

        foreach ($this->_localColumnNames as $column) {
            $columns[] = $column->getQuotedName($platform);
        }

        return $columns;
    }

    /**
     * Returns unquoted representation of local table column names for comparison with other FK
     *
     * @return string[]
     */
    public function getUnquotedLocalColumns()
    {
        return array_map([$this, 'trimQuotes'], $this->getLocalColumns());
    }

    /**
     * Returns unquoted representation of foreign table column names for comparison with other FK
     *
     * @return string[]
     */
    public function getUnquotedForeignColumns()
    {
        return array_map([$this, 'trimQuotes'], $this->getForeignColumns());
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use {@see getLocalColumns()} instead.
     *
     * @see getLocalColumns
     */
    public function getColumns()
    {
        return $this->getLocalColumns();
    }

    /**
     * Returns the quoted representation of the referencing table column names
     * the foreign key constraint is associated with.
     *
     * But only if they were defined with one or the referencing table column name
     * is a keyword reserved by the platform.
     * Otherwise the plain unquoted value as inserted is returned.
     *
     * @deprecated Use {@see getQuotedLocalColumns()} instead.
     *
     * @see getQuotedLocalColumns
     *
     * @param AbstractPlatform $platform The platform to use for quotation.
     *
     * @return string[]
     */
    public function getQuotedColumns(AbstractPlatform $platform)
    {
        return $this->getQuotedLocalColumns($platform);
    }

    /**
     * Returns the name of the referenced table
     * the foreign key constraint is associated with.
     *
     * @return string
     */
    public function getForeignTableName()
    {
        return $this->_foreignTableName->getName();
    }

    /**
     * Returns the non-schema qualified foreign table name.
     *
     * @return string
     */
    public function getUnqualifiedForeignTableName()
    {
        $name     = $this->_foreignTableName->getName();
        $position = strrpos($name, '.');

        if ($position !== false) {
            $name = substr($name, $position + 1);
        }

        if ($this->isIdentifierQuoted($name)) {
            $name = $this->trimQuotes($name);
        }

        return strtolower($name);
    }

    /**
     * Returns the quoted representation of the referenced table name
     * the foreign key constraint is associated with.
     *
     * But only if it was defined with one or the referenced table name
     * is a keyword reserved by the platform.
     * Otherwise the plain unquoted value as inserted is returned.
     *
     * @param AbstractPlatform $platform The platform to use for quotation.
     *
     * @return string
     */
    public function getQuotedForeignTableName(AbstractPlatform $platform)
    {
        return $this->_foreignTableName->getQuotedName($platform);
    }

    /**
     * Returns the names of the referenced table columns
     * the foreign key constraint is associated with.
     *
     * @return string[]
     */
    public function getForeignColumns()
    {
        return array_keys($this->_foreignColumnNames);
    }

    /**
     * Returns the quoted representation of the referenced table column names
     * the foreign key constraint is associated with.
     *
     * But only if they were defined with one or the referenced table column name
     * is a keyword reserved by the platform.
     * Otherwise the plain unquoted value as inserted is returned.
     *
     * @param AbstractPlatform $platform The platform to use for quotation.
     *
     * @return string[]
     */
    public function getQuotedForeignColumns(AbstractPlatform $platform)
    {
        $columns = [];

        foreach ($this->_foreignColumnNames as $column) {
            $columns[] = $column->getQuotedName($platform);
        }

        return $columns;
    }

    /**
     * Returns whether or not a given option
     * is associated with the foreign key constraint.
     *
     * @param string $name Name of the option to check.
     *
     * @return bool
     */
    public function hasOption($name)
    {
        return isset($this->_options[$name]);
    }

    /**
     * Returns an option associated with the foreign key constraint.
     *
     * @param string $name Name of the option the foreign key constraint is associated with.
     *
     * @return mixed
     */
    public function getOption($name)
    {
        return $this->_options[$name];
    }

    /**
     * Returns the options associated with the foreign key constraint.
     *
     * @return mixed[]
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Returns the referential action for UPDATE operations
     * on the referenced table the foreign key constraint is associated with.
     *
     * @return string|null
     */
    public function onUpdate()
    {
        return $this->onEvent('onUpdate');
    }

    /**
     * Returns the referential action for DELETE operations
     * on the referenced table the foreign key constraint is associated with.
     *
     * @return string|null
     */
    public function onDelete()
    {
        return $this->onEvent('onDelete');
    }

    /**
     * Returns the referential action for a given database operation
     * on the referenced table the foreign key constraint is associated with.
     *
     * @param string $event Name of the database operation/event to return the referential action for.
     */
    private function onEvent($event): ?string
    {
        if (isset($this->_options[$event])) {
            $onEvent = strtoupper($this->_options[$event]);

            if ($onEvent !== 'NO ACTION' && $onEvent !== 'RESTRICT') {
                return $onEvent;
            }
        }

        return null;
    }

    /**
     * Checks whether this foreign key constraint intersects the given index columns.
     *
     * Returns `true` if at least one of this foreign key's local columns
     * matches one of the given index's columns, `false` otherwise.
     *
     * @param Index $index The index to be checked against.
     *
     * @return bool
     */
    public function intersectsIndexColumns(Index $index)
    {
        foreach ($index->getColumns() as $indexColumn) {
            foreach ($this->_localColumnNames as $localColumn) {
                if (strtolower($indexColumn) === strtolower($localColumn->getName())) {
                    return true;
                }
            }
        }

        return false;
    }
}
