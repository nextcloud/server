<?php

namespace Doctrine\DBAL;

/**
 * Container for all DBAL events.
 *
 * This class cannot be instantiated.
 *
 * @deprecated
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

    /** @deprecated */
    public const postConnect = 'postConnect';

    /** @deprecated */
    public const onSchemaCreateTable = 'onSchemaCreateTable';

    /** @deprecated */
    public const onSchemaCreateTableColumn = 'onSchemaCreateTableColumn';

    /** @deprecated */
    public const onSchemaDropTable = 'onSchemaDropTable';

    /** @deprecated */
    public const onSchemaAlterTable = 'onSchemaAlterTable';

    /** @deprecated */
    public const onSchemaAlterTableAddColumn = 'onSchemaAlterTableAddColumn';

    /** @deprecated */
    public const onSchemaAlterTableRemoveColumn = 'onSchemaAlterTableRemoveColumn';

    /** @deprecated */
    public const onSchemaAlterTableChangeColumn = 'onSchemaAlterTableChangeColumn';

    /** @deprecated */
    public const onSchemaAlterTableRenameColumn = 'onSchemaAlterTableRenameColumn';

    /** @deprecated */
    public const onSchemaColumnDefinition = 'onSchemaColumnDefinition';

    /** @deprecated */
    public const onSchemaIndexDefinition = 'onSchemaIndexDefinition';

    /** @deprecated */
    public const onTransactionBegin = 'onTransactionBegin';

    /** @deprecated */
    public const onTransactionCommit = 'onTransactionCommit';

    /** @deprecated */
    public const onTransactionRollBack = 'onTransactionRollBack';
}
