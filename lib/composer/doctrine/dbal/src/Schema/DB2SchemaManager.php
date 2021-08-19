<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\DB2Platform;
use Doctrine\DBAL\Types\Type;

use function array_change_key_case;
use function assert;
use function preg_match;
use function str_replace;
use function strpos;
use function strtolower;
use function substr;

use const CASE_LOWER;

/**
 * IBM Db2 Schema Manager.
 */
class DB2SchemaManager extends AbstractSchemaManager
{
    /**
     * {@inheritdoc}
     *
     * Apparently creator is the schema not the user who created it:
     * {@link http://publib.boulder.ibm.com/infocenter/dzichelp/v2r2/index.jsp?topic=/com.ibm.db29.doc.sqlref/db2z_sysibmsystablestable.htm}
     */
    public function listTableNames()
    {
        $sql = $this->_platform->getListTablesSQL() . ' AND CREATOR = CURRENT_USER';

        $tables = $this->_conn->fetchAllAssociative($sql);

        return $this->filterAssetNames($this->_getPortableTablesList($tables));
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        $length    = null;
        $fixed     = null;
        $scale     = false;
        $precision = false;

        $default = null;

        if ($tableColumn['default'] !== null && $tableColumn['default'] !== 'NULL') {
            $default = $tableColumn['default'];

            if (preg_match('/^\'(.*)\'$/s', $default, $matches) === 1) {
                $default = str_replace("''", "'", $matches[1]);
            }
        }

        $type = $this->_platform->getDoctrineTypeMapping($tableColumn['typename']);

        if (isset($tableColumn['comment'])) {
            $type                   = $this->extractDoctrineTypeFromComment($tableColumn['comment'], $type);
            $tableColumn['comment'] = $this->removeDoctrineTypeFromComment($tableColumn['comment'], $type);
        }

        switch (strtolower($tableColumn['typename'])) {
            case 'varchar':
                $length = $tableColumn['length'];
                $fixed  = false;
                break;

            case 'character':
                $length = $tableColumn['length'];
                $fixed  = true;
                break;

            case 'clob':
                $length = $tableColumn['length'];
                break;

            case 'decimal':
            case 'double':
            case 'real':
                $scale     = $tableColumn['scale'];
                $precision = $tableColumn['length'];
                break;
        }

        $options = [
            'length'        => $length,
            'unsigned'      => false,
            'fixed'         => (bool) $fixed,
            'default'       => $default,
            'autoincrement' => (bool) $tableColumn['autoincrement'],
            'notnull'       => (bool) ($tableColumn['nulls'] === 'N'),
            'scale'         => null,
            'precision'     => null,
            'comment'       => isset($tableColumn['comment']) && $tableColumn['comment'] !== ''
                ? $tableColumn['comment']
                : null,
            'platformOptions' => [],
        ];

        if ($scale !== null && $precision !== null) {
            $options['scale']     = $scale;
            $options['precision'] = $precision;
        }

        return new Column($tableColumn['colname'], Type::getType($type), $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTablesList($tables)
    {
        $tableNames = [];
        foreach ($tables as $tableRow) {
            $tableRow     = array_change_key_case($tableRow, CASE_LOWER);
            $tableNames[] = $tableRow['name'];
        }

        return $tableNames;
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTableIndexesList($tableIndexes, $tableName = null)
    {
        foreach ($tableIndexes as &$tableIndexRow) {
            $tableIndexRow            = array_change_key_case($tableIndexRow, CASE_LOWER);
            $tableIndexRow['primary'] = (bool) $tableIndexRow['primary'];
        }

        return parent::_getPortableTableIndexesList($tableIndexes, $tableName);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTableForeignKeyDefinition($tableForeignKey)
    {
        return new ForeignKeyConstraint(
            $tableForeignKey['local_columns'],
            $tableForeignKey['foreign_table'],
            $tableForeignKey['foreign_columns'],
            $tableForeignKey['name'],
            $tableForeignKey['options']
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTableForeignKeysList($tableForeignKeys)
    {
        $foreignKeys = [];

        foreach ($tableForeignKeys as $tableForeignKey) {
            $tableForeignKey = array_change_key_case($tableForeignKey, CASE_LOWER);

            if (! isset($foreignKeys[$tableForeignKey['index_name']])) {
                $foreignKeys[$tableForeignKey['index_name']] = [
                    'local_columns'   => [$tableForeignKey['local_column']],
                    'foreign_table'   => $tableForeignKey['foreign_table'],
                    'foreign_columns' => [$tableForeignKey['foreign_column']],
                    'name'            => $tableForeignKey['index_name'],
                    'options'         => [
                        'onUpdate' => $tableForeignKey['on_update'],
                        'onDelete' => $tableForeignKey['on_delete'],
                    ],
                ];
            } else {
                $foreignKeys[$tableForeignKey['index_name']]['local_columns'][]   = $tableForeignKey['local_column'];
                $foreignKeys[$tableForeignKey['index_name']]['foreign_columns'][] = $tableForeignKey['foreign_column'];
            }
        }

        return parent::_getPortableTableForeignKeysList($foreignKeys);
    }

    /**
     * @param string $def
     *
     * @return string|null
     */
    protected function _getPortableForeignKeyRuleDef($def)
    {
        if ($def === 'C') {
            return 'CASCADE';
        }

        if ($def === 'N') {
            return 'SET NULL';
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableViewDefinition($view)
    {
        $view = array_change_key_case($view, CASE_LOWER);

        $sql = '';
        $pos = strpos($view['text'], ' AS ');

        if ($pos !== false) {
            $sql = substr($view['text'], $pos + 4);
        }

        return new View($view['name'], $sql);
    }

    /**
     * {@inheritdoc}
     */
    public function listTableDetails($name): Table
    {
        $table = parent::listTableDetails($name);

        $platform = $this->_platform;
        assert($platform instanceof DB2Platform);
        $sql = $platform->getListTableCommentsSQL($name);

        $tableOptions = $this->_conn->fetchAssociative($sql);

        if ($tableOptions !== false) {
            $table->addOption('comment', $tableOptions['REMARKS']);
        }

        return $table;
    }
}
