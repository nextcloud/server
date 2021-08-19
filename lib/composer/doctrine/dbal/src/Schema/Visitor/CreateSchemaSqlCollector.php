<?php

namespace Doctrine\DBAL\Schema\Visitor;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;

use function array_merge;

class CreateSchemaSqlCollector extends AbstractVisitor
{
    /** @var string[] */
    private $createNamespaceQueries = [];

    /** @var string[] */
    private $createTableQueries = [];

    /** @var string[] */
    private $createSequenceQueries = [];

    /** @var string[] */
    private $createFkConstraintQueries = [];

    /** @var AbstractPlatform */
    private $platform;

    public function __construct(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function acceptNamespace($namespaceName)
    {
        if (! $this->platform->supportsSchemas()) {
            return;
        }

        $this->createNamespaceQueries[] = $this->platform->getCreateSchemaSQL($namespaceName);
    }

    /**
     * {@inheritdoc}
     */
    public function acceptTable(Table $table)
    {
        $this->createTableQueries = array_merge($this->createTableQueries, $this->platform->getCreateTableSQL($table));
    }

    /**
     * {@inheritdoc}
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
        if (! $this->platform->supportsForeignKeyConstraints()) {
            return;
        }

        $this->createFkConstraintQueries[] = $this->platform->getCreateForeignKeySQL($fkConstraint, $localTable);
    }

    /**
     * {@inheritdoc}
     */
    public function acceptSequence(Sequence $sequence)
    {
        $this->createSequenceQueries[] = $this->platform->getCreateSequenceSQL($sequence);
    }

    /**
     * @return void
     */
    public function resetQueries()
    {
        $this->createNamespaceQueries    = [];
        $this->createTableQueries        = [];
        $this->createSequenceQueries     = [];
        $this->createFkConstraintQueries = [];
    }

    /**
     * Gets all queries collected so far.
     *
     * @return string[]
     */
    public function getQueries()
    {
        return array_merge(
            $this->createNamespaceQueries,
            $this->createTableQueries,
            $this->createSequenceQueries,
            $this->createFkConstraintQueries
        );
    }
}
