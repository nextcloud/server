<?php

namespace Doctrine\DBAL\Schema\Visitor;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;

/**
 * Removes assets from a schema that are not in the default namespace.
 *
 * Some databases such as MySQL support cross databases joins, but don't
 * allow to call DDLs to a database from another connected database.
 * Before a schema is serialized into SQL this visitor can cleanup schemas with
 * non default namespaces.
 *
 * This visitor filters all these non-default namespaced tables and sequences
 * and removes them from the SChema instance.
 */
class RemoveNamespacedAssets extends AbstractVisitor
{
    /** @var Schema */
    private $schema;

    /**
     * {@inheritdoc}
     */
    public function acceptSchema(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function acceptTable(Table $table)
    {
        if ($table->isInDefaultNamespace($this->schema->getName())) {
            return;
        }

        $this->schema->dropTable($table->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function acceptSequence(Sequence $sequence)
    {
        if ($sequence->isInDefaultNamespace($this->schema->getName())) {
            return;
        }

        $this->schema->dropSequence($sequence->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
        // The table may already be deleted in a previous
        // RemoveNamespacedAssets#acceptTable call. Removing Foreign keys that
        // point to nowhere.
        if (! $this->schema->hasTable($fkConstraint->getForeignTableName())) {
            $localTable->removeForeignKey($fkConstraint->getName());

            return;
        }

        $foreignTable = $this->schema->getTable($fkConstraint->getForeignTableName());
        if ($foreignTable->isInDefaultNamespace($this->schema->getName())) {
            return;
        }

        $localTable->removeForeignKey($fkConstraint->getName());
    }
}
