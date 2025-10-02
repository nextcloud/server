<?php

namespace Doctrine\DBAL\Schema\Visitor;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Deprecations\Deprecation;
use SplObjectStorage;

use function assert;
use function strlen;

/**
 * Gathers SQL statements that allow to completely drop the current schema.
 *
 * @deprecated Use {@link DropSchemaObjectsSQLBuilder} instead.
 */
class DropSchemaSqlCollector extends AbstractVisitor
{
    private SplObjectStorage $constraints;
    private SplObjectStorage $sequences;
    private SplObjectStorage $tables;
    private AbstractPlatform $platform;

    public function __construct(AbstractPlatform $platform)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5416',
            'DropSchemaSqlCollector is deprecated. Use DropSchemaObjectsSQLBuilder instead.',
        );

        $this->platform = $platform;
        $this->initializeQueries();
    }

    /**
     * {@inheritDoc}
     */
    public function acceptTable(Table $table)
    {
        $this->tables->attach($table);
    }

    /**
     * {@inheritDoc}
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
        if (strlen($fkConstraint->getName()) === 0) {
            throw SchemaException::namedForeignKeyRequired($localTable, $fkConstraint);
        }

        $this->constraints->attach($fkConstraint, $localTable);
    }

    /**
     * {@inheritDoc}
     */
    public function acceptSequence(Sequence $sequence)
    {
        $this->sequences->attach($sequence);
    }

    /** @return void */
    public function clearQueries()
    {
        $this->initializeQueries();
    }

    /** @return string[] */
    public function getQueries()
    {
        $sql = [];

        foreach ($this->constraints as $fkConstraint) {
            assert($fkConstraint instanceof ForeignKeyConstraint);
            $localTable = $this->constraints[$fkConstraint];
            $sql[]      = $this->platform->getDropForeignKeySQL(
                $fkConstraint->getQuotedName($this->platform),
                $localTable->getQuotedName($this->platform),
            );
        }

        foreach ($this->sequences as $sequence) {
            assert($sequence instanceof Sequence);
            $sql[] = $this->platform->getDropSequenceSQL($sequence->getQuotedName($this->platform));
        }

        foreach ($this->tables as $table) {
            assert($table instanceof Table);
            $sql[] = $this->platform->getDropTableSQL($table->getQuotedName($this->platform));
        }

        return $sql;
    }

    private function initializeQueries(): void
    {
        $this->constraints = new SplObjectStorage();
        $this->sequences   = new SplObjectStorage();
        $this->tables      = new SplObjectStorage();
    }
}
