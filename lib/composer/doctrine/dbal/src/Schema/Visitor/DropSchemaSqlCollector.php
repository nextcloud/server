<?php

namespace Doctrine\DBAL\Schema\Visitor;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use SplObjectStorage;

use function assert;
use function strlen;

/**
 * Gathers SQL statements that allow to completely drop the current schema.
 */
class DropSchemaSqlCollector extends AbstractVisitor
{
    /** @var SplObjectStorage */
    private $constraints;

    /** @var SplObjectStorage */
    private $sequences;

    /** @var SplObjectStorage */
    private $tables;

    /** @var AbstractPlatform */
    private $platform;

    public function __construct(AbstractPlatform $platform)
    {
        $this->platform = $platform;
        $this->clearQueries();
    }

    /**
     * {@inheritdoc}
     */
    public function acceptTable(Table $table)
    {
        $this->tables->attach($table);
    }

    /**
     * {@inheritdoc}
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
        if (strlen($fkConstraint->getName()) === 0) {
            throw SchemaException::namedForeignKeyRequired($localTable, $fkConstraint);
        }

        $this->constraints->attach($fkConstraint, $localTable);
    }

    /**
     * {@inheritdoc}
     */
    public function acceptSequence(Sequence $sequence)
    {
        $this->sequences->attach($sequence);
    }

    /**
     * @return void
     */
    public function clearQueries()
    {
        $this->constraints = new SplObjectStorage();
        $this->sequences   = new SplObjectStorage();
        $this->tables      = new SplObjectStorage();
    }

    /**
     * @return string[]
     */
    public function getQueries()
    {
        $sql = [];

        foreach ($this->constraints as $fkConstraint) {
            assert($fkConstraint instanceof ForeignKeyConstraint);
            $localTable = $this->constraints[$fkConstraint];
            $sql[]      = $this->platform->getDropForeignKeySQL($fkConstraint, $localTable);
        }

        foreach ($this->sequences as $sequence) {
            assert($sequence instanceof Sequence);
            $sql[] = $this->platform->getDropSequenceSQL($sequence);
        }

        foreach ($this->tables as $table) {
            assert($table instanceof Table);
            $sql[] = $this->platform->getDropTableSQL($table);
        }

        return $sql;
    }
}
