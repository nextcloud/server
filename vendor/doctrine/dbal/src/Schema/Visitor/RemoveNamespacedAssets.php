<?php

namespace Doctrine\DBAL\Schema\Visitor;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Deprecations\Deprecation;

/**
 * Removes assets from a schema that are not in the default namespace.
 *
 * Some databases such as MySQL support cross databases joins, but don't
 * allow to call DDLs to a database from another connected database.
 * Before a schema is serialized into SQL this visitor can cleanup schemas with
 * non default namespaces.
 *
 * This visitor filters all these non-default namespaced tables and sequences
 * and removes them from the Schema instance.
 *
 * @deprecated Do not use namespaces if the target database platform doesn't support them.
 */
class RemoveNamespacedAssets extends AbstractVisitor
{
    private ?Schema $schema = null;

    public function __construct()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5432',
            'RemoveNamespacedAssets is deprecated. Do not use namespaces'
                . " if the target database platform doesn't support them.",
        );
    }

    /**
     * {@inheritDoc}
     */
    public function acceptSchema(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * {@inheritDoc}
     */
    public function acceptTable(Table $table)
    {
        if ($this->schema === null) {
            return;
        }

        if ($table->isInDefaultNamespace($this->schema->getName())) {
            return;
        }

        $this->schema->dropTable($table->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function acceptSequence(Sequence $sequence)
    {
        if ($this->schema === null) {
            return;
        }

        if ($sequence->isInDefaultNamespace($this->schema->getName())) {
            return;
        }

        $this->schema->dropSequence($sequence->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
        if ($this->schema === null) {
            return;
        }

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
