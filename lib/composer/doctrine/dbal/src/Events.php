<?php

namespace Doctrine\DBAL;

/**
 * Container for all DBAL events.
 *
 * This class cannot be instantiated.
 */
final class Events
{
    /**
     * Private constructor. This class cannot be instantiated.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    public const postConnect = 'postConnect';

    public const onSchemaCreateTable            = 'onSchemaCreateTable';
    public const onSchemaCreateTableColumn      = 'onSchemaCreateTableColumn';
    public const onSchemaDropTable              = 'onSchemaDropTable';
    public const onSchemaAlterTable             = 'onSchemaAlterTable';
    public const onSchemaAlterTableAddColumn    = 'onSchemaAlterTableAddColumn';
    public const onSchemaAlterTableRemoveColumn = 'onSchemaAlterTableRemoveColumn';
    public const onSchemaAlterTableChangeColumn = 'onSchemaAlterTableChangeColumn';
    public const onSchemaAlterTableRenameColumn = 'onSchemaAlterTableRenameColumn';
    public const onSchemaColumnDefinition       = 'onSchemaColumnDefinition';
    public const onSchemaIndexDefinition        = 'onSchemaIndexDefinition';
}
