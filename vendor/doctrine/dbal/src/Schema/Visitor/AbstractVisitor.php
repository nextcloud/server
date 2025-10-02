<?php

namespace Doctrine\DBAL\Schema\Visitor;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;

/**
 * Abstract Visitor with empty methods for easy extension.
 *
 * @deprecated
 */
class AbstractVisitor implements Visitor, NamespaceVisitor
{
    public function acceptSchema(Schema $schema)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function acceptNamespace($namespaceName)
    {
    }

    public function acceptTable(Table $table)
    {
    }

    public function acceptColumn(Table $table, Column $column)
    {
    }

    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
    }

    public function acceptIndex(Table $table, Index $index)
    {
    }

    public function acceptSequence(Sequence $sequence)
    {
    }
}
