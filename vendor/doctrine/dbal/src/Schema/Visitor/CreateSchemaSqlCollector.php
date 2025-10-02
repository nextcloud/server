<?php

namespace Doctrine\DBAL\Schema\Visitor;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Deprecations\Deprecation;

use function array_merge;

/** @deprecated Use {@link CreateSchemaObjectsSQLBuilder} instead. */
class CreateSchemaSqlCollector extends AbstractVisitor
{
    /** @var string[] */
    private array $createNamespaceQueries = [];

    /** @var string[] */
    private array $createTableQueries = [];

    /** @var string[] */
    private array $createSequenceQueries = [];

    /** @var string[] */
    private array $createFkConstraintQueries = [];

    private AbstractPlatform $platform;

    public function __construct(AbstractPlatform $platform)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5416',
            'CreateSchemaSqlCollector is deprecated. Use CreateSchemaObjectsSQLBuilder instead.',
        );

        $this->platform = $platform;
    }

    /**
     * {@inheritDoc}
     */
    public function acceptNamespace($namespaceName)
    {
        if (! $this->platform->supportsSchemas()) {
            return;
        }

        $this->createNamespaceQueries[] = $this->platform->getCreateSchemaSQL($namespaceName);
    }

    /**
     * {@inheritDoc}
     */
    public function acceptTable(Table $table)
    {
        $this->createTableQueries = array_merge($this->createTableQueries, $this->platform->getCreateTableSQL($table));
    }

    /**
     * {@inheritDoc}
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
        if (! $this->platform->supportsForeignKeyConstraints()) {
            return;
        }

        $this->createFkConstraintQueries[] = $this->platform->getCreateForeignKeySQL($fkConstraint, $localTable);
    }

    /**
     * {@inheritDoc}
     */
    public function acceptSequence(Sequence $sequence)
    {
        $this->createSequenceQueries[] = $this->platform->getCreateSequenceSQL($sequence);
    }

    /** @return void */
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
            $this->createSequenceQueries,
            $this->createTableQueries,
            $this->createFkConstraintQueries,
        );
    }
}
