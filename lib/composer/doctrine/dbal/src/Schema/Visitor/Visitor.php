<?php

namespace Doctrine\DBAL\Schema\Visitor;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;

/**
 * Schema Visitor used for Validation or Generation purposes.
 */
interface Visitor
{
    /**
     * @return void
     *
     * @throws SchemaException
     */
    public function acceptSchema(Schema $schema);

    /**
     * @return void
     */
    public function acceptTable(Table $table);

    /**
     * @return void
     */
    public function acceptColumn(Table $table, Column $column);

    /**
     * @return void
     *
     * @throws SchemaException
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint);

    /**
     * @return void
     */
    public function acceptIndex(Table $table, Index $index);

    /**
     * @return void
     */
    public function acceptSequence(Sequence $sequence);
}
