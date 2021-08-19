<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Visitor\CreateSchemaSqlCollector;
use Doctrine\DBAL\Schema\Visitor\DropSchemaSqlCollector;
use Doctrine\DBAL\Schema\Visitor\NamespaceVisitor;
use Doctrine\DBAL\Schema\Visitor\Visitor;

use function array_keys;
use function strpos;
use function strtolower;

/**
 * Object representation of a database schema.
 *
 * Different vendors have very inconsistent naming with regard to the concept
 * of a "schema". Doctrine understands a schema as the entity that conceptually
 * wraps a set of database objects such as tables, sequences, indexes and
 * foreign keys that belong to each other into a namespace. A Doctrine Schema
 * has nothing to do with the "SCHEMA" defined as in PostgreSQL, it is more
 * related to the concept of "DATABASE" that exists in MySQL and PostgreSQL.
 *
 * Every asset in the doctrine schema has a name. A name consists of either a
 * namespace.local name pair or just a local unqualified name.
 *
 * The abstraction layer that covers a PostgreSQL schema is the namespace of an
 * database object (asset). A schema can have a name, which will be used as
 * default namespace for the unqualified database objects that are created in
 * the schema.
 *
 * In the case of MySQL where cross-database queries are allowed this leads to
 * databases being "misinterpreted" as namespaces. This is intentional, however
 * the CREATE/DROP SQL visitors will just filter this queries and do not
 * execute them. Only the queries for the currently connected database are
 * executed.
 */
class Schema extends AbstractAsset
{
    /**
     * The namespaces in this schema.
     *
     * @var string[]
     */
    private $namespaces = [];

    /** @var Table[] */
    protected $_tables = [];

    /** @var Sequence[] */
    protected $_sequences = [];

    /** @var SchemaConfig */
    protected $_schemaConfig;

    /**
     * @param Table[]    $tables
     * @param Sequence[] $sequences
     * @param string[]   $namespaces
     *
     * @throws SchemaException
     */
    public function __construct(
        array $tables = [],
        array $sequences = [],
        ?SchemaConfig $schemaConfig = null,
        array $namespaces = []
    ) {
        if ($schemaConfig === null) {
            $schemaConfig = new SchemaConfig();
        }

        $this->_schemaConfig = $schemaConfig;
        $this->_setName($schemaConfig->getName() ?? 'public');

        foreach ($namespaces as $namespace) {
            $this->createNamespace($namespace);
        }

        foreach ($tables as $table) {
            $this->_addTable($table);
        }

        foreach ($sequences as $sequence) {
            $this->_addSequence($sequence);
        }
    }

    /**
     * @return bool
     */
    public function hasExplicitForeignKeyIndexes()
    {
        return $this->_schemaConfig->hasExplicitForeignKeyIndexes();
    }

    /**
     * @return void
     *
     * @throws SchemaException
     */
    protected function _addTable(Table $table)
    {
        $namespaceName = $table->getNamespaceName();
        $tableName     = $table->getFullQualifiedName($this->getName());

        if (isset($this->_tables[$tableName])) {
            throw SchemaException::tableAlreadyExists($tableName);
        }

        if (
            $namespaceName !== null
            && ! $table->isInDefaultNamespace($this->getName())
            && ! $this->hasNamespace($namespaceName)
        ) {
            $this->createNamespace($namespaceName);
        }

        $this->_tables[$tableName] = $table;
        $table->setSchemaConfig($this->_schemaConfig);
    }

    /**
     * @return void
     *
     * @throws SchemaException
     */
    protected function _addSequence(Sequence $sequence)
    {
        $namespaceName = $sequence->getNamespaceName();
        $seqName       = $sequence->getFullQualifiedName($this->getName());

        if (isset($this->_sequences[$seqName])) {
            throw SchemaException::sequenceAlreadyExists($seqName);
        }

        if (
            $namespaceName !== null
            && ! $sequence->isInDefaultNamespace($this->getName())
            && ! $this->hasNamespace($namespaceName)
        ) {
            $this->createNamespace($namespaceName);
        }

        $this->_sequences[$seqName] = $sequence;
    }

    /**
     * Returns the namespaces of this schema.
     *
     * @return string[] A list of namespace names.
     */
    public function getNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * Gets all tables of this schema.
     *
     * @return Table[]
     */
    public function getTables()
    {
        return $this->_tables;
    }

    /**
     * @param string $name
     *
     * @return Table
     *
     * @throws SchemaException
     */
    public function getTable($name)
    {
        $name = $this->getFullQualifiedAssetName($name);
        if (! isset($this->_tables[$name])) {
            throw SchemaException::tableDoesNotExist($name);
        }

        return $this->_tables[$name];
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getFullQualifiedAssetName($name)
    {
        $name = $this->getUnquotedAssetName($name);

        if (strpos($name, '.') === false) {
            $name = $this->getName() . '.' . $name;
        }

        return strtolower($name);
    }

    /**
     * Returns the unquoted representation of a given asset name.
     *
     * @param string $assetName Quoted or unquoted representation of an asset name.
     *
     * @return string
     */
    private function getUnquotedAssetName($assetName)
    {
        if ($this->isIdentifierQuoted($assetName)) {
            return $this->trimQuotes($assetName);
        }

        return $assetName;
    }

    /**
     * Does this schema have a namespace with the given name?
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasNamespace($name)
    {
        $name = strtolower($this->getUnquotedAssetName($name));

        return isset($this->namespaces[$name]);
    }

    /**
     * Does this schema have a table with the given name?
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasTable($name)
    {
        $name = $this->getFullQualifiedAssetName($name);

        return isset($this->_tables[$name]);
    }

    /**
     * Gets all table names, prefixed with a schema name, even the default one if present.
     *
     * @return string[]
     */
    public function getTableNames()
    {
        return array_keys($this->_tables);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasSequence($name)
    {
        $name = $this->getFullQualifiedAssetName($name);

        return isset($this->_sequences[$name]);
    }

    /**
     * @param string $name
     *
     * @return Sequence
     *
     * @throws SchemaException
     */
    public function getSequence($name)
    {
        $name = $this->getFullQualifiedAssetName($name);
        if (! $this->hasSequence($name)) {
            throw SchemaException::sequenceDoesNotExist($name);
        }

        return $this->_sequences[$name];
    }

    /**
     * @return Sequence[]
     */
    public function getSequences()
    {
        return $this->_sequences;
    }

    /**
     * Creates a new namespace.
     *
     * @param string $name The name of the namespace to create.
     *
     * @return Schema This schema instance.
     *
     * @throws SchemaException
     */
    public function createNamespace($name)
    {
        $unquotedName = strtolower($this->getUnquotedAssetName($name));

        if (isset($this->namespaces[$unquotedName])) {
            throw SchemaException::namespaceAlreadyExists($unquotedName);
        }

        $this->namespaces[$unquotedName] = $name;

        return $this;
    }

    /**
     * Creates a new table.
     *
     * @param string $name
     *
     * @return Table
     *
     * @throws SchemaException
     */
    public function createTable($name)
    {
        $table = new Table($name);
        $this->_addTable($table);

        foreach ($this->_schemaConfig->getDefaultTableOptions() as $option => $value) {
            $table->addOption($option, $value);
        }

        return $table;
    }

    /**
     * Renames a table.
     *
     * @param string $oldName
     * @param string $newName
     *
     * @return Schema
     *
     * @throws SchemaException
     */
    public function renameTable($oldName, $newName)
    {
        $table = $this->getTable($oldName);
        $table->_setName($newName);

        $this->dropTable($oldName);
        $this->_addTable($table);

        return $this;
    }

    /**
     * Drops a table from the schema.
     *
     * @param string $name
     *
     * @return Schema
     *
     * @throws SchemaException
     */
    public function dropTable($name)
    {
        $name = $this->getFullQualifiedAssetName($name);
        $this->getTable($name);
        unset($this->_tables[$name]);

        return $this;
    }

    /**
     * Creates a new sequence.
     *
     * @param string $name
     * @param int    $allocationSize
     * @param int    $initialValue
     *
     * @return Sequence
     *
     * @throws SchemaException
     */
    public function createSequence($name, $allocationSize = 1, $initialValue = 1)
    {
        $seq = new Sequence($name, $allocationSize, $initialValue);
        $this->_addSequence($seq);

        return $seq;
    }

    /**
     * @param string $name
     *
     * @return Schema
     */
    public function dropSequence($name)
    {
        $name = $this->getFullQualifiedAssetName($name);
        unset($this->_sequences[$name]);

        return $this;
    }

    /**
     * Returns an array of necessary SQL queries to create the schema on the given platform.
     *
     * @return string[]
     */
    public function toSql(AbstractPlatform $platform)
    {
        $sqlCollector = new CreateSchemaSqlCollector($platform);
        $this->visit($sqlCollector);

        return $sqlCollector->getQueries();
    }

    /**
     * Return an array of necessary SQL queries to drop the schema on the given platform.
     *
     * @return string[]
     */
    public function toDropSql(AbstractPlatform $platform)
    {
        $dropSqlCollector = new DropSchemaSqlCollector($platform);
        $this->visit($dropSqlCollector);

        return $dropSqlCollector->getQueries();
    }

    /**
     * @return string[]
     *
     * @throws SchemaException
     */
    public function getMigrateToSql(Schema $toSchema, AbstractPlatform $platform)
    {
        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($this, $toSchema);

        return $schemaDiff->toSql($platform);
    }

    /**
     * @return string[]
     *
     * @throws SchemaException
     */
    public function getMigrateFromSql(Schema $fromSchema, AbstractPlatform $platform)
    {
        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $this);

        return $schemaDiff->toSql($platform);
    }

    /**
     * @return void
     */
    public function visit(Visitor $visitor)
    {
        $visitor->acceptSchema($this);

        if ($visitor instanceof NamespaceVisitor) {
            foreach ($this->namespaces as $namespace) {
                $visitor->acceptNamespace($namespace);
            }
        }

        foreach ($this->_tables as $table) {
            $table->visit($visitor);
        }

        foreach ($this->_sequences as $sequence) {
            $sequence->visit($visitor);
        }
    }

    /**
     * Cloning a Schema triggers a deep clone of all related assets.
     *
     * @return void
     */
    public function __clone()
    {
        foreach ($this->_tables as $k => $table) {
            $this->_tables[$k] = clone $table;
        }

        foreach ($this->_sequences as $k => $sequence) {
            $this->_sequences[$k] = clone $sequence;
        }
    }
}
