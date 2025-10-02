<?php

namespace Doctrine\DBAL\Platforms;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Event\SchemaAlterTableAddColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableChangeColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableRemoveColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableRenameColumnEventArgs;
use Doctrine\DBAL\Event\SchemaCreateTableColumnEventArgs;
use Doctrine\DBAL\Event\SchemaCreateTableEventArgs;
use Doctrine\DBAL\Event\SchemaDropTableEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\InvalidLockMode;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Platforms\Keywords\KeywordList;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Constraint;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Schema\UniqueConstraint;
use Doctrine\DBAL\SQL\Builder\DefaultSelectSQLBuilder;
use Doctrine\DBAL\SQL\Builder\SelectSQLBuilder;
use Doctrine\DBAL\SQL\Parser;
use Doctrine\DBAL\TransactionIsolationLevel;
use Doctrine\DBAL\Types;
use Doctrine\DBAL\Types\Type;
use Doctrine\Deprecations\Deprecation;
use InvalidArgumentException;
use UnexpectedValueException;

use function addcslashes;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function assert;
use function count;
use function explode;
use function func_get_arg;
use function func_get_args;
use function func_num_args;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function preg_quote;
use function preg_replace;
use function sprintf;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function strtoupper;

/**
 * Base class for all DatabasePlatforms. The DatabasePlatforms are the central
 * point of abstraction of platform-specific behaviors, features and SQL dialects.
 * They are a passive source of information.
 *
 * @todo Remove any unnecessary methods.
 */
abstract class AbstractPlatform
{
    public const CREATE_INDEXES = 1;

    public const CREATE_FOREIGNKEYS = 2;

    /** @var string[]|null */
    protected $doctrineTypeMapping;

    /**
     * Contains a list of all columns that should generate parseable column comments for type-detection
     * in reverse engineering scenarios.
     *
     * @deprecated This property is deprecated and will be removed in Doctrine DBAL 4.0.
     *
     * @var string[]|null
     */
    protected $doctrineTypeComments;

    /**
     * @deprecated
     *
     * @var EventManager|null
     */
    protected $_eventManager;

    /**
     * Holds the KeywordList instance for the current platform.
     *
     * @var KeywordList|null
     */
    protected $_keywords;

    private bool $disableTypeComments = false;

    /** @internal */
    final public function setDisableTypeComments(bool $value): void
    {
        $this->disableTypeComments = $value;
    }

    /**
     * Sets the EventManager used by the Platform.
     *
     * @deprecated
     *
     * @return void
     */
    public function setEventManager(EventManager $eventManager)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/5784',
            '%s is deprecated.',
            __METHOD__,
        );

        $this->_eventManager = $eventManager;
    }

    /**
     * Gets the EventManager used by the Platform.
     *
     * @deprecated
     *
     * @return EventManager|null
     */
    public function getEventManager()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/5784',
            '%s is deprecated.',
            __METHOD__,
        );

        return $this->_eventManager;
    }

    /**
     * Returns the SQL snippet that declares a boolean column.
     *
     * @param mixed[] $column
     *
     * @return string
     */
    abstract public function getBooleanTypeDeclarationSQL(array $column);

    /**
     * Returns the SQL snippet that declares a 4 byte integer column.
     *
     * @param mixed[] $column
     *
     * @return string
     */
    abstract public function getIntegerTypeDeclarationSQL(array $column);

    /**
     * Returns the SQL snippet that declares an 8 byte integer column.
     *
     * @param mixed[] $column
     *
     * @return string
     */
    abstract public function getBigIntTypeDeclarationSQL(array $column);

    /**
     * Returns the SQL snippet that declares a 2 byte integer column.
     *
     * @param mixed[] $column
     *
     * @return string
     */
    abstract public function getSmallIntTypeDeclarationSQL(array $column);

    /**
     * Returns the SQL snippet that declares common properties of an integer column.
     *
     * @param mixed[] $column
     *
     * @return string
     */
    abstract protected function _getCommonIntegerTypeDeclarationSQL(array $column);

    /**
     * Lazy load Doctrine Type Mappings.
     *
     * @return void
     */
    abstract protected function initializeDoctrineTypeMappings();

    /**
     * Initializes Doctrine Type Mappings with the platform defaults
     * and with all additional type mappings.
     */
    private function initializeAllDoctrineTypeMappings(): void
    {
        $this->initializeDoctrineTypeMappings();

        foreach (Type::getTypesMap() as $typeName => $className) {
            foreach (Type::getType($typeName)->getMappedDatabaseTypes($this) as $dbType) {
                $dbType                             = strtolower($dbType);
                $this->doctrineTypeMapping[$dbType] = $typeName;
            }
        }
    }

    /**
     * Returns the SQL snippet used to declare a column that can
     * store characters in the ASCII character set
     *
     * @param mixed[] $column
     */
    public function getAsciiStringTypeDeclarationSQL(array $column): string
    {
        return $this->getStringTypeDeclarationSQL($column);
    }

    /**
     * Returns the SQL snippet used to declare a VARCHAR column type.
     *
     * @deprecated Use {@link getStringTypeDeclarationSQL()} instead.
     *
     * @param mixed[] $column
     *
     * @return string
     */
    public function getVarcharTypeDeclarationSQL(array $column)
    {
        if (isset($column['length'])) {
            $lengthOmitted = false;
        } else {
            $column['length'] = $this->getVarcharDefaultLength();
            $lengthOmitted    = true;
        }

        $fixed = $column['fixed'] ?? false;

        $maxLength = $fixed
            ? $this->getCharMaxLength()
            : $this->getVarcharMaxLength();

        if ($column['length'] > $maxLength) {
            return $this->getClobTypeDeclarationSQL($column);
        }

        return $this->getVarcharTypeDeclarationSQLSnippet($column['length'], $fixed, $lengthOmitted);
    }

    /**
     * Returns the SQL snippet used to declare a string column type.
     *
     * @param mixed[] $column
     *
     * @return string
     */
    public function getStringTypeDeclarationSQL(array $column)
    {
        return $this->getVarcharTypeDeclarationSQL($column);
    }

    /**
     * Returns the SQL snippet used to declare a BINARY/VARBINARY column type.
     *
     * @param mixed[] $column The column definition.
     *
     * @return string
     */
    public function getBinaryTypeDeclarationSQL(array $column)
    {
        if (isset($column['length'])) {
            $lengthOmitted = false;
        } else {
            $column['length'] = $this->getBinaryDefaultLength();
            $lengthOmitted    = true;
        }

        $fixed = $column['fixed'] ?? false;

        $maxLength = $this->getBinaryMaxLength();

        if ($column['length'] > $maxLength) {
            if ($maxLength > 0) {
                Deprecation::trigger(
                    'doctrine/dbal',
                    'https://github.com/doctrine/dbal/issues/3187',
                    'Binary column length %d is greater than supported by the platform (%d).'
                        . ' Reduce the column length or use a BLOB column instead.',
                    $column['length'],
                    $maxLength,
                );
            }

            return $this->getBlobTypeDeclarationSQL($column);
        }

        return $this->getBinaryTypeDeclarationSQLSnippet($column['length'], $fixed, $lengthOmitted);
    }

    /**
     * Returns the SQL snippet to declare a GUID/UUID column.
     *
     * By default this maps directly to a CHAR(36) and only maps to more
     * special datatypes when the underlying databases support this datatype.
     *
     * @param mixed[] $column
     *
     * @return string
     */
    public function getGuidTypeDeclarationSQL(array $column)
    {
        $column['length'] = 36;
        $column['fixed']  = true;

        return $this->getStringTypeDeclarationSQL($column);
    }

    /**
     * Returns the SQL snippet to declare a JSON column.
     *
     * By default this maps directly to a CLOB and only maps to more
     * special datatypes when the underlying databases support this datatype.
     *
     * @param mixed[] $column
     *
     * @return string
     */
    public function getJsonTypeDeclarationSQL(array $column)
    {
        return $this->getClobTypeDeclarationSQL($column);
    }

    /**
     * @param int|false $length
     * @param bool      $fixed
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    protected function getVarcharTypeDeclarationSQLSnippet($length, $fixed/*, $lengthOmitted = false*/)
    {
        throw Exception::notSupported('VARCHARs not supported by Platform.');
    }

    /**
     * Returns the SQL snippet used to declare a BINARY/VARBINARY column type.
     *
     * @param int|false $length The length of the column.
     * @param bool      $fixed  Whether the column length is fixed.
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    protected function getBinaryTypeDeclarationSQLSnippet($length, $fixed/*, $lengthOmitted = false*/)
    {
        throw Exception::notSupported('BINARY/VARBINARY column types are not supported by this platform.');
    }

    /**
     * Returns the SQL snippet used to declare a CLOB column type.
     *
     * @param mixed[] $column
     *
     * @return string
     */
    abstract public function getClobTypeDeclarationSQL(array $column);

    /**
     * Returns the SQL Snippet used to declare a BLOB column type.
     *
     * @param mixed[] $column
     *
     * @return string
     */
    abstract public function getBlobTypeDeclarationSQL(array $column);

    /**
     * Gets the name of the platform.
     *
     * @deprecated Identify platforms by their class.
     *
     * @return string
     */
    abstract public function getName();

    /**
     * Registers a doctrine type to be used in conjunction with a column type of this platform.
     *
     * @param string $dbType
     * @param string $doctrineType
     *
     * @return void
     *
     * @throws Exception If the type is not found.
     */
    public function registerDoctrineTypeMapping($dbType, $doctrineType)
    {
        if ($this->doctrineTypeMapping === null) {
            $this->initializeAllDoctrineTypeMappings();
        }

        if (! Types\Type::hasType($doctrineType)) {
            throw Exception::typeNotFound($doctrineType);
        }

        $dbType                             = strtolower($dbType);
        $this->doctrineTypeMapping[$dbType] = $doctrineType;

        $doctrineType = Type::getType($doctrineType);

        if (! $doctrineType->requiresSQLCommentHint($this)) {
            return;
        }

        $this->markDoctrineTypeCommented($doctrineType);
    }

    /**
     * Gets the Doctrine type that is mapped for the given database column type.
     *
     * @param string $dbType
     *
     * @return string
     *
     * @throws Exception
     */
    public function getDoctrineTypeMapping($dbType)
    {
        if ($this->doctrineTypeMapping === null) {
            $this->initializeAllDoctrineTypeMappings();
        }

        $dbType = strtolower($dbType);

        if (! isset($this->doctrineTypeMapping[$dbType])) {
            throw new Exception(
                'Unknown database type ' . $dbType . ' requested, ' . static::class . ' may not support it.',
            );
        }

        return $this->doctrineTypeMapping[$dbType];
    }

    /**
     * Checks if a database type is currently supported by this platform.
     *
     * @param string $dbType
     *
     * @return bool
     */
    public function hasDoctrineTypeMappingFor($dbType)
    {
        if ($this->doctrineTypeMapping === null) {
            $this->initializeAllDoctrineTypeMappings();
        }

        $dbType = strtolower($dbType);

        return isset($this->doctrineTypeMapping[$dbType]);
    }

    /**
     * Initializes the Doctrine Type comments instance variable for in_array() checks.
     *
     * @deprecated This API will be removed in Doctrine DBAL 4.0.
     *
     * @return void
     */
    protected function initializeCommentedDoctrineTypes()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5058',
            '%s is deprecated and will be removed in Doctrine DBAL 4.0.',
            __METHOD__,
        );

        $this->doctrineTypeComments = [];

        foreach (Type::getTypesMap() as $typeName => $className) {
            $type = Type::getType($typeName);

            if (! $type->requiresSQLCommentHint($this)) {
                continue;
            }

            $this->doctrineTypeComments[] = $typeName;
        }
    }

    /**
     * Is it necessary for the platform to add a parsable type comment to allow reverse engineering the given type?
     *
     * @deprecated Use {@link Type::requiresSQLCommentHint()} instead.
     *
     * @return bool
     */
    public function isCommentedDoctrineType(Type $doctrineType)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5058',
            '%s is deprecated and will be removed in Doctrine DBAL 4.0. Use Type::requiresSQLCommentHint() instead.',
            __METHOD__,
        );

        if ($this->doctrineTypeComments === null) {
            $this->initializeCommentedDoctrineTypes();
        }

        return $doctrineType->requiresSQLCommentHint($this);
    }

    /**
     * Marks this type as to be commented in ALTER TABLE and CREATE TABLE statements.
     *
     * @param string|Type $doctrineType
     *
     * @return void
     */
    public function markDoctrineTypeCommented($doctrineType)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5058',
            '%s is deprecated and will be removed in Doctrine DBAL 4.0. Use Type::requiresSQLCommentHint() instead.',
            __METHOD__,
        );

        if ($this->doctrineTypeComments === null) {
            $this->initializeCommentedDoctrineTypes();
        }

        assert(is_array($this->doctrineTypeComments));

        $this->doctrineTypeComments[] = $doctrineType instanceof Type ? $doctrineType->getName() : $doctrineType;
    }

    /**
     * Gets the comment to append to a column comment that helps parsing this type in reverse engineering.
     *
     * @deprecated This method will be removed without replacement.
     *
     * @return string
     */
    public function getDoctrineTypeComment(Type $doctrineType)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5107',
            '%s is deprecated and will be removed in Doctrine DBAL 4.0.',
            __METHOD__,
        );

        return '(DC2Type:' . $doctrineType->getName() . ')';
    }

    /**
     * Gets the comment of a passed column modified by potential doctrine type comment hints.
     *
     * @deprecated This method will be removed without replacement.
     *
     * @return string|null
     */
    protected function getColumnComment(Column $column)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5107',
            '%s is deprecated and will be removed in Doctrine DBAL 4.0.',
            __METHOD__,
        );

        $comment = $column->getComment();

        if (! $this->disableTypeComments && $column->getType()->requiresSQLCommentHint($this)) {
            $comment .= $this->getDoctrineTypeComment($column->getType());
        }

        return $comment;
    }

    /**
     * Gets the character used for identifier quoting.
     *
     * @deprecated Use {@see quoteIdentifier()} to quote identifiers instead.
     *
     * @return string
     */
    public function getIdentifierQuoteCharacter()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5388',
            'AbstractPlatform::getIdentifierQuoteCharacter() is deprecated. Use quoteIdentifier() instead.',
        );

        return '"';
    }

    /**
     * Gets the string portion that starts an SQL comment.
     *
     * @deprecated
     *
     * @return string
     */
    public function getSqlCommentStartString()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getSqlCommentStartString() is deprecated.',
        );

        return '--';
    }

    /**
     * Gets the string portion that ends an SQL comment.
     *
     * @deprecated
     *
     * @return string
     */
    public function getSqlCommentEndString()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getSqlCommentEndString() is deprecated.',
        );

        return "\n";
    }

    /**
     * Gets the maximum length of a char column.
     *
     * @deprecated
     */
    public function getCharMaxLength(): int
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/3263',
            'AbstractPlatform::getCharMaxLength() is deprecated.',
        );

        return $this->getVarcharMaxLength();
    }

    /**
     * Gets the maximum length of a varchar column.
     *
     * @deprecated
     *
     * @return int
     */
    public function getVarcharMaxLength()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/3263',
            'AbstractPlatform::getVarcharMaxLength() is deprecated.',
        );

        return 4000;
    }

    /**
     * Gets the default length of a varchar column.
     *
     * @deprecated
     *
     * @return int
     */
    public function getVarcharDefaultLength()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/3263',
            'Relying on the default varchar column length is deprecated, specify the length explicitly.',
        );

        return 255;
    }

    /**
     * Gets the maximum length of a binary column.
     *
     * @deprecated
     *
     * @return int
     */
    public function getBinaryMaxLength()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/3263',
            'AbstractPlatform::getBinaryMaxLength() is deprecated.',
        );

        return 4000;
    }

    /**
     * Gets the default length of a binary column.
     *
     * @deprecated
     *
     * @return int
     */
    public function getBinaryDefaultLength()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/3263',
            'Relying on the default binary column length is deprecated, specify the length explicitly.',
        );

        return 255;
    }

    /**
     * Gets all SQL wildcard characters of the platform.
     *
     * @deprecated Use {@see AbstractPlatform::getLikeWildcardCharacters()} instead.
     *
     * @return string[]
     */
    public function getWildcards()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getWildcards() is deprecated.'
            . ' Use AbstractPlatform::getLikeWildcardCharacters() instead.',
        );

        return ['%', '_'];
    }

    /**
     * Returns the regular expression operator.
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getRegexpExpression()
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * Returns the SQL snippet to get the average value of a column.
     *
     * @deprecated Use AVG() in SQL instead.
     *
     * @param string $column The column to use.
     *
     * @return string Generated SQL including an AVG aggregate function.
     */
    public function getAvgExpression($column)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getAvgExpression() is deprecated. Use AVG() in SQL instead.',
        );

        return 'AVG(' . $column . ')';
    }

    /**
     * Returns the SQL snippet to get the number of rows (without a NULL value) of a column.
     *
     * If a '*' is used instead of a column the number of selected rows is returned.
     *
     * @deprecated Use COUNT() in SQL instead.
     *
     * @param string|int $column The column to use.
     *
     * @return string Generated SQL including a COUNT aggregate function.
     */
    public function getCountExpression($column)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getCountExpression() is deprecated. Use COUNT() in SQL instead.',
        );

        return 'COUNT(' . $column . ')';
    }

    /**
     * Returns the SQL snippet to get the highest value of a column.
     *
     * @deprecated Use MAX() in SQL instead.
     *
     * @param string $column The column to use.
     *
     * @return string Generated SQL including a MAX aggregate function.
     */
    public function getMaxExpression($column)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getMaxExpression() is deprecated. Use MAX() in SQL instead.',
        );

        return 'MAX(' . $column . ')';
    }

    /**
     * Returns the SQL snippet to get the lowest value of a column.
     *
     * @deprecated Use MIN() in SQL instead.
     *
     * @param string $column The column to use.
     *
     * @return string Generated SQL including a MIN aggregate function.
     */
    public function getMinExpression($column)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getMinExpression() is deprecated. Use MIN() in SQL instead.',
        );

        return 'MIN(' . $column . ')';
    }

    /**
     * Returns the SQL snippet to get the total sum of a column.
     *
     * @deprecated Use SUM() in SQL instead.
     *
     * @param string $column The column to use.
     *
     * @return string Generated SQL including a SUM aggregate function.
     */
    public function getSumExpression($column)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getSumExpression() is deprecated. Use SUM() in SQL instead.',
        );

        return 'SUM(' . $column . ')';
    }

    // scalar functions

    /**
     * Returns the SQL snippet to get the md5 sum of a column.
     *
     * Note: Not SQL92, but common functionality.
     *
     * @deprecated
     *
     * @param string $column
     *
     * @return string
     */
    public function getMd5Expression($column)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getMd5Expression() is deprecated.',
        );

        return 'MD5(' . $column . ')';
    }

    /**
     * Returns the SQL snippet to get the length of a text column in characters.
     *
     * @param string $column
     *
     * @return string
     */
    public function getLengthExpression($column)
    {
        return 'LENGTH(' . $column . ')';
    }

    /**
     * Returns the SQL snippet to get the squared value of a column.
     *
     * @deprecated Use SQRT() in SQL instead.
     *
     * @param string $column The column to use.
     *
     * @return string Generated SQL including an SQRT aggregate function.
     */
    public function getSqrtExpression($column)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getSqrtExpression() is deprecated. Use SQRT() in SQL instead.',
        );

        return 'SQRT(' . $column . ')';
    }

    /**
     * Returns the SQL snippet to round a numeric column to the number of decimals specified.
     *
     * @deprecated Use ROUND() in SQL instead.
     *
     * @param string     $column
     * @param string|int $decimals
     *
     * @return string
     */
    public function getRoundExpression($column, $decimals = 0)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getRoundExpression() is deprecated. Use ROUND() in SQL instead.',
        );

        return 'ROUND(' . $column . ', ' . $decimals . ')';
    }

    /**
     * Returns the SQL snippet to get the remainder of the division operation $expression1 / $expression2.
     *
     * @param string $expression1
     * @param string $expression2
     *
     * @return string
     */
    public function getModExpression($expression1, $expression2)
    {
        return 'MOD(' . $expression1 . ', ' . $expression2 . ')';
    }

    /**
     * Returns the SQL snippet to trim a string.
     *
     * @param string      $str  The expression to apply the trim to.
     * @param int         $mode The position of the trim (leading/trailing/both).
     * @param string|bool $char The char to trim, has to be quoted already. Defaults to space.
     *
     * @return string
     */
    public function getTrimExpression($str, $mode = TrimMode::UNSPECIFIED, $char = false)
    {
        $expression = '';

        switch ($mode) {
            case TrimMode::LEADING:
                $expression = 'LEADING ';
                break;

            case TrimMode::TRAILING:
                $expression = 'TRAILING ';
                break;

            case TrimMode::BOTH:
                $expression = 'BOTH ';
                break;
        }

        if ($char !== false) {
            $expression .= $char . ' ';
        }

        if ($mode !== TrimMode::UNSPECIFIED || $char !== false) {
            $expression .= 'FROM ';
        }

        return 'TRIM(' . $expression . $str . ')';
    }

    /**
     * Returns the SQL snippet to trim trailing space characters from the expression.
     *
     * @deprecated Use RTRIM() in SQL instead.
     *
     * @param string $str Literal string or column name.
     *
     * @return string
     */
    public function getRtrimExpression($str)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getRtrimExpression() is deprecated. Use RTRIM() in SQL instead.',
        );

        return 'RTRIM(' . $str . ')';
    }

    /**
     * Returns the SQL snippet to trim leading space characters from the expression.
     *
     * @deprecated Use LTRIM() in SQL instead.
     *
     * @param string $str Literal string or column name.
     *
     * @return string
     */
    public function getLtrimExpression($str)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getLtrimExpression() is deprecated. Use LTRIM() in SQL instead.',
        );

        return 'LTRIM(' . $str . ')';
    }

    /**
     * Returns the SQL snippet to change all characters from the expression to uppercase,
     * according to the current character set mapping.
     *
     * @deprecated Use UPPER() in SQL instead.
     *
     * @param string $str Literal string or column name.
     *
     * @return string
     */
    public function getUpperExpression($str)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getUpperExpression() is deprecated. Use UPPER() in SQL instead.',
        );

        return 'UPPER(' . $str . ')';
    }

    /**
     * Returns the SQL snippet to change all characters from the expression to lowercase,
     * according to the current character set mapping.
     *
     * @deprecated Use LOWER() in SQL instead.
     *
     * @param string $str Literal string or column name.
     *
     * @return string
     */
    public function getLowerExpression($str)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getLowerExpression() is deprecated. Use LOWER() in SQL instead.',
        );

        return 'LOWER(' . $str . ')';
    }

    /**
     * Returns the SQL snippet to get the position of the first occurrence of substring $substr in string $str.
     *
     * @param string           $str      Literal string.
     * @param string           $substr   Literal string to find.
     * @param string|int|false $startPos Position to start at, beginning of string by default.
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getLocateExpression($str, $substr, $startPos = false)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * Returns the SQL snippet to get the current system date.
     *
     * @deprecated Generate dates within the application.
     *
     * @return string
     */
    public function getNowExpression()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4753',
            'AbstractPlatform::getNowExpression() is deprecated. Generate dates within the application.',
        );

        return 'NOW()';
    }

    /**
     * Returns a SQL snippet to get a substring inside an SQL statement.
     *
     * Note: Not SQL92, but common functionality.
     *
     * SQLite only supports the 2 parameter variant of this function.
     *
     * @param string          $string An sql string literal or column name/alias.
     * @param string|int      $start  Where to start the substring portion.
     * @param string|int|null $length The substring portion length.
     *
     * @return string
     */
    public function getSubstringExpression($string, $start, $length = null)
    {
        if ($length === null) {
            return 'SUBSTRING(' . $string . ' FROM ' . $start . ')';
        }

        return 'SUBSTRING(' . $string . ' FROM ' . $start . ' FOR ' . $length . ')';
    }

    /**
     * Returns a SQL snippet to concatenate the given expressions.
     *
     * Accepts an arbitrary number of string parameters. Each parameter must contain an expression.
     *
     * @return string
     */
    public function getConcatExpression()
    {
        return implode(' || ', func_get_args());
    }

    /**
     * Returns the SQL for a logical not.
     *
     * Example:
     * <code>
     * $q = new Doctrine_Query();
     * $e = $q->expr;
     * $q->select('*')->from('table')
     *   ->where($e->eq('id', $e->not('null'));
     * </code>
     *
     * @deprecated Use NOT() in SQL instead.
     *
     * @param string $expression
     *
     * @return string The logical expression.
     */
    public function getNotExpression($expression)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getNotExpression() is deprecated. Use NOT() in SQL instead.',
        );

        return 'NOT(' . $expression . ')';
    }

    /**
     * Returns the SQL that checks if an expression is null.
     *
     * @deprecated Use IS NULL in SQL instead.
     *
     * @param string $expression The expression that should be compared to null.
     *
     * @return string The logical expression.
     */
    public function getIsNullExpression($expression)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getIsNullExpression() is deprecated. Use IS NULL in SQL instead.',
        );

        return $expression . ' IS NULL';
    }

    /**
     * Returns the SQL that checks if an expression is not null.
     *
     * @deprecated Use IS NOT NULL in SQL instead.
     *
     * @param string $expression The expression that should be compared to null.
     *
     * @return string The logical expression.
     */
    public function getIsNotNullExpression($expression)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getIsNotNullExpression() is deprecated. Use IS NOT NULL in SQL instead.',
        );

        return $expression . ' IS NOT NULL';
    }

    /**
     * Returns the SQL that checks if an expression evaluates to a value between two values.
     *
     * The parameter $expression is checked if it is between $value1 and $value2.
     *
     * Note: There is a slight difference in the way BETWEEN works on some databases.
     * http://www.w3schools.com/sql/sql_between.asp. If you want complete database
     * independence you should avoid using between().
     *
     * @deprecated Use BETWEEN in SQL instead.
     *
     * @param string $expression The value to compare to.
     * @param string $value1     The lower value to compare with.
     * @param string $value2     The higher value to compare with.
     *
     * @return string The logical expression.
     */
    public function getBetweenExpression($expression, $value1, $value2)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getBetweenExpression() is deprecated. Use BETWEEN in SQL instead.',
        );

        return $expression . ' BETWEEN ' . $value1 . ' AND ' . $value2;
    }

    /**
     * Returns the SQL to get the arccosine of a value.
     *
     * @deprecated Use ACOS() in SQL instead.
     *
     * @param string $value
     *
     * @return string
     */
    public function getAcosExpression($value)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getAcosExpression() is deprecated. Use ACOS() in SQL instead.',
        );

        return 'ACOS(' . $value . ')';
    }

    /**
     * Returns the SQL to get the sine of a value.
     *
     * @deprecated Use SIN() in SQL instead.
     *
     * @param string $value
     *
     * @return string
     */
    public function getSinExpression($value)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getSinExpression() is deprecated. Use SIN() in SQL instead.',
        );

        return 'SIN(' . $value . ')';
    }

    /**
     * Returns the SQL to get the PI value.
     *
     * @deprecated Use PI() in SQL instead.
     *
     * @return string
     */
    public function getPiExpression()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getPiExpression() is deprecated. Use PI() in SQL instead.',
        );

        return 'PI()';
    }

    /**
     * Returns the SQL to get the cosine of a value.
     *
     * @deprecated Use COS() in SQL instead.
     *
     * @param string $value
     *
     * @return string
     */
    public function getCosExpression($value)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getCosExpression() is deprecated. Use COS() in SQL instead.',
        );

        return 'COS(' . $value . ')';
    }

    /**
     * Returns the SQL to calculate the difference in days between the two passed dates.
     *
     * Computes diff = date1 - date2.
     *
     * @param string $date1
     * @param string $date2
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateDiffExpression($date1, $date2)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * Returns the SQL to add the number of given seconds to a date.
     *
     * @param string     $date
     * @param int|string $seconds
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateAddSecondsExpression($date, $seconds)
    {
        if (is_int($seconds)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/3498',
                'Passing $seconds as an integer is deprecated. Pass it as a numeric string instead.',
            );
        }

        return $this->getDateArithmeticIntervalExpression($date, '+', $seconds, DateIntervalUnit::SECOND);
    }

    /**
     * Returns the SQL to subtract the number of given seconds from a date.
     *
     * @param string     $date
     * @param int|string $seconds
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateSubSecondsExpression($date, $seconds)
    {
        if (is_int($seconds)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/3498',
                'Passing $seconds as an integer is deprecated. Pass it as a numeric string instead.',
            );
        }

        return $this->getDateArithmeticIntervalExpression($date, '-', $seconds, DateIntervalUnit::SECOND);
    }

    /**
     * Returns the SQL to add the number of given minutes to a date.
     *
     * @param string     $date
     * @param int|string $minutes
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateAddMinutesExpression($date, $minutes)
    {
        if (is_int($minutes)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/3498',
                'Passing $minutes as an integer is deprecated. Pass it as a numeric string instead.',
            );
        }

        return $this->getDateArithmeticIntervalExpression($date, '+', $minutes, DateIntervalUnit::MINUTE);
    }

    /**
     * Returns the SQL to subtract the number of given minutes from a date.
     *
     * @param string     $date
     * @param int|string $minutes
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateSubMinutesExpression($date, $minutes)
    {
        if (is_int($minutes)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/3498',
                'Passing $minutes as an integer is deprecated. Pass it as a numeric string instead.',
            );
        }

        return $this->getDateArithmeticIntervalExpression($date, '-', $minutes, DateIntervalUnit::MINUTE);
    }

    /**
     * Returns the SQL to add the number of given hours to a date.
     *
     * @param string     $date
     * @param int|string $hours
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateAddHourExpression($date, $hours)
    {
        if (is_int($hours)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/3498',
                'Passing $hours as an integer is deprecated. Pass it as a numeric string instead.',
            );
        }

        return $this->getDateArithmeticIntervalExpression($date, '+', $hours, DateIntervalUnit::HOUR);
    }

    /**
     * Returns the SQL to subtract the number of given hours to a date.
     *
     * @param string     $date
     * @param int|string $hours
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateSubHourExpression($date, $hours)
    {
        if (is_int($hours)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/3498',
                'Passing $hours as an integer is deprecated. Pass it as a numeric string instead.',
            );
        }

        return $this->getDateArithmeticIntervalExpression($date, '-', $hours, DateIntervalUnit::HOUR);
    }

    /**
     * Returns the SQL to add the number of given days to a date.
     *
     * @param string     $date
     * @param int|string $days
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateAddDaysExpression($date, $days)
    {
        if (is_int($days)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/3498',
                'Passing $days as an integer is deprecated. Pass it as a numeric string instead.',
            );
        }

        return $this->getDateArithmeticIntervalExpression($date, '+', $days, DateIntervalUnit::DAY);
    }

    /**
     * Returns the SQL to subtract the number of given days to a date.
     *
     * @param string     $date
     * @param int|string $days
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateSubDaysExpression($date, $days)
    {
        if (is_int($days)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/3498',
                'Passing $days as an integer is deprecated. Pass it as a numeric string instead.',
            );
        }

        return $this->getDateArithmeticIntervalExpression($date, '-', $days, DateIntervalUnit::DAY);
    }

    /**
     * Returns the SQL to add the number of given weeks to a date.
     *
     * @param string     $date
     * @param int|string $weeks
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateAddWeeksExpression($date, $weeks)
    {
        if (is_int($weeks)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/3498',
                'Passing $weeks as an integer is deprecated. Pass it as a numeric string instead.',
            );
        }

        return $this->getDateArithmeticIntervalExpression($date, '+', $weeks, DateIntervalUnit::WEEK);
    }

    /**
     * Returns the SQL to subtract the number of given weeks from a date.
     *
     * @param string     $date
     * @param int|string $weeks
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateSubWeeksExpression($date, $weeks)
    {
        if (is_int($weeks)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/3498',
                'Passing $weeks as an integer is deprecated. Pass it as a numeric string instead.',
            );
        }

        return $this->getDateArithmeticIntervalExpression($date, '-', $weeks, DateIntervalUnit::WEEK);
    }

    /**
     * Returns the SQL to add the number of given months to a date.
     *
     * @param string     $date
     * @param int|string $months
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateAddMonthExpression($date, $months)
    {
        if (is_int($months)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/3498',
                'Passing $months as an integer is deprecated. Pass it as a numeric string instead.',
            );
        }

        return $this->getDateArithmeticIntervalExpression($date, '+', $months, DateIntervalUnit::MONTH);
    }

    /**
     * Returns the SQL to subtract the number of given months to a date.
     *
     * @param string     $date
     * @param int|string $months
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateSubMonthExpression($date, $months)
    {
        if (is_int($months)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/3498',
                'Passing $months as an integer is deprecated. Pass it as a numeric string instead.',
            );
        }

        return $this->getDateArithmeticIntervalExpression($date, '-', $months, DateIntervalUnit::MONTH);
    }

    /**
     * Returns the SQL to add the number of given quarters to a date.
     *
     * @param string     $date
     * @param int|string $quarters
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateAddQuartersExpression($date, $quarters)
    {
        if (is_int($quarters)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/3498',
                'Passing $quarters as an integer is deprecated. Pass it as a numeric string instead.',
            );
        }

        return $this->getDateArithmeticIntervalExpression($date, '+', $quarters, DateIntervalUnit::QUARTER);
    }

    /**
     * Returns the SQL to subtract the number of given quarters from a date.
     *
     * @param string     $date
     * @param int|string $quarters
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateSubQuartersExpression($date, $quarters)
    {
        if (is_int($quarters)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/3498',
                'Passing $quarters as an integer is deprecated. Pass it as a numeric string instead.',
            );
        }

        return $this->getDateArithmeticIntervalExpression($date, '-', $quarters, DateIntervalUnit::QUARTER);
    }

    /**
     * Returns the SQL to add the number of given years to a date.
     *
     * @param string     $date
     * @param int|string $years
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateAddYearsExpression($date, $years)
    {
        if (is_int($years)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/3498',
                'Passing $years as an integer is deprecated. Pass it as a numeric string instead.',
            );
        }

        return $this->getDateArithmeticIntervalExpression($date, '+', $years, DateIntervalUnit::YEAR);
    }

    /**
     * Returns the SQL to subtract the number of given years from a date.
     *
     * @param string     $date
     * @param int|string $years
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateSubYearsExpression($date, $years)
    {
        if (is_int($years)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/3498',
                'Passing $years as an integer is deprecated. Pass it as a numeric string instead.',
            );
        }

        return $this->getDateArithmeticIntervalExpression($date, '-', $years, DateIntervalUnit::YEAR);
    }

    /**
     * Returns the SQL for a date arithmetic expression.
     *
     * @param string     $date     The column or literal representing a date
     *                                     to perform the arithmetic operation on.
     * @param string     $operator The arithmetic operator (+ or -).
     * @param int|string $interval The interval that shall be calculated into the date.
     * @param string     $unit     The unit of the interval that shall be calculated into the date.
     *                                     One of the {@see DateIntervalUnit} constants.
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    protected function getDateArithmeticIntervalExpression($date, $operator, $interval, $unit)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * Generates the SQL expression which represents the given date interval multiplied by a number
     *
     * @param string $interval   SQL expression describing the interval value
     * @param int    $multiplier Interval multiplier
     */
    protected function multiplyInterval(string $interval, int $multiplier): string
    {
        return sprintf('(%s * %d)', $interval, $multiplier);
    }

    /**
     * Returns the SQL bit AND comparison expression.
     *
     * @param string $value1
     * @param string $value2
     *
     * @return string
     */
    public function getBitAndComparisonExpression($value1, $value2)
    {
        return '(' . $value1 . ' & ' . $value2 . ')';
    }

    /**
     * Returns the SQL bit OR comparison expression.
     *
     * @param string $value1
     * @param string $value2
     *
     * @return string
     */
    public function getBitOrComparisonExpression($value1, $value2)
    {
        return '(' . $value1 . ' | ' . $value2 . ')';
    }

    /**
     * Returns the SQL expression which represents the currently selected database.
     */
    abstract public function getCurrentDatabaseExpression(): string;

    /**
     * Returns the FOR UPDATE expression.
     *
     * @deprecated This API is not portable. Use {@link QueryBuilder::forUpdate()}` instead.
     *
     * @return string
     */
    public function getForUpdateSQL()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/6191',
            '%s is deprecated as non-portable.',
            __METHOD__,
        );

        return 'FOR UPDATE';
    }

    /**
     * Honors that some SQL vendors such as MsSql use table hints for locking instead of the
     * ANSI SQL FOR UPDATE specification.
     *
     * @param string $fromClause The FROM clause to append the hint for the given lock mode to
     * @param int    $lockMode   One of the Doctrine\DBAL\LockMode::* constants
     * @phpstan-param LockMode::* $lockMode
     */
    public function appendLockHint(string $fromClause, int $lockMode): string
    {
        switch ($lockMode) {
            case LockMode::NONE:
            case LockMode::OPTIMISTIC:
            case LockMode::PESSIMISTIC_READ:
            case LockMode::PESSIMISTIC_WRITE:
                return $fromClause;

            default:
                throw InvalidLockMode::fromLockMode($lockMode);
        }
    }

    /**
     * Returns the SQL snippet to append to any SELECT statement which locks rows in shared read lock.
     *
     * This defaults to the ANSI SQL "FOR UPDATE", which is an exclusive lock (Write). Some database
     * vendors allow to lighten this constraint up to be a real read lock.
     *
     * @deprecated This API is not portable.
     *
     * @return string
     */
    public function getReadLockSQL()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/6191',
            '%s is deprecated as non-portable.',
            __METHOD__,
        );

        return $this->getForUpdateSQL();
    }

    /**
     * Returns the SQL snippet to append to any SELECT statement which obtains an exclusive lock on the rows.
     *
     * The semantics of this lock mode should equal the SELECT .. FOR UPDATE of the ANSI SQL standard.
     *
     * @deprecated This API is not portable.
     *
     * @return string
     */
    public function getWriteLockSQL()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/6191',
            '%s is deprecated as non-portable.',
            __METHOD__,
        );

        return $this->getForUpdateSQL();
    }

    /**
     * Returns the SQL snippet to drop an existing table.
     *
     * @param Table|string $table
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getDropTableSQL($table)
    {
        $tableArg = $table;

        if ($table instanceof Table) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $table as a Table object to %s is deprecated. Pass it as a quoted name instead.',
                __METHOD__,
            );

            $table = $table->getQuotedName($this);
        }

        if (! is_string($table)) {
            throw new InvalidArgumentException(
                __METHOD__ . '() expects $table parameter to be string or ' . Table::class . '.',
            );
        }

        if ($this->_eventManager !== null && $this->_eventManager->hasListeners(Events::onSchemaDropTable)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/5784',
                'Subscribing to %s events is deprecated.',
                Events::onSchemaDropTable,
            );

            $eventArgs = new SchemaDropTableEventArgs($tableArg, $this);
            $this->_eventManager->dispatchEvent(Events::onSchemaDropTable, $eventArgs);

            if ($eventArgs->isDefaultPrevented()) {
                $sql = $eventArgs->getSql();

                if ($sql === null) {
                    throw new UnexpectedValueException('Default implementation of DROP TABLE was overridden with NULL');
                }

                return $sql;
            }
        }

        return 'DROP TABLE ' . $table;
    }

    /**
     * Returns the SQL to safely drop a temporary table WITHOUT implicitly committing an open transaction.
     *
     * @param Table|string $table
     *
     * @return string
     */
    public function getDropTemporaryTableSQL($table)
    {
        if ($table instanceof Table) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $table as a Table object to %s is deprecated. Pass it as a quoted name instead.',
                __METHOD__,
            );

            $table = $table->getQuotedName($this);
        }

        return $this->getDropTableSQL($table);
    }

    /**
     * Returns the SQL to drop an index from a table.
     *
     * @param Index|string      $index
     * @param Table|string|null $table
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getDropIndexSQL($index, $table = null)
    {
        if ($index instanceof Index) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $index as an Index object to %s is deprecated. Pass it as a quoted name instead.',
                __METHOD__,
            );

            $index = $index->getQuotedName($this);
        } elseif (! is_string($index)) {
            throw new InvalidArgumentException(
                __METHOD__ . '() expects $index parameter to be string or ' . Index::class . '.',
            );
        }

        return 'DROP INDEX ' . $index;
    }

    /**
     * Returns the SQL to drop a constraint.
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
     *
     * @param Constraint|string $constraint
     * @param Table|string      $table
     *
     * @return string
     */
    public function getDropConstraintSQL($constraint, $table)
    {
        if ($constraint instanceof Constraint) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $constraint as a Constraint object to %s is deprecated. Pass it as a quoted name instead.',
                __METHOD__,
            );
        } else {
            $constraint = new Identifier($constraint);
        }

        if ($table instanceof Table) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $table as a Table object to %s is deprecated. Pass it as a quoted name instead.',
                __METHOD__,
            );
        } else {
            $table = new Identifier($table);
        }

        $constraint = $constraint->getQuotedName($this);
        $table      = $table->getQuotedName($this);

        return 'ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $constraint;
    }

    /**
     * Returns the SQL to drop a foreign key.
     *
     * @param ForeignKeyConstraint|string $foreignKey
     * @param Table|string                $table
     *
     * @return string
     */
    public function getDropForeignKeySQL($foreignKey, $table)
    {
        if ($foreignKey instanceof ForeignKeyConstraint) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $foreignKey as a ForeignKeyConstraint object to %s is deprecated.'
                    . ' Pass it as a quoted name instead.',
                __METHOD__,
            );
        } else {
            $foreignKey = new Identifier($foreignKey);
        }

        if ($table instanceof Table) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $table as a Table object to %s is deprecated. Pass it as a quoted name instead.',
                __METHOD__,
            );
        } else {
            $table = new Identifier($table);
        }

        $foreignKey = $foreignKey->getQuotedName($this);
        $table      = $table->getQuotedName($this);

        return 'ALTER TABLE ' . $table . ' DROP FOREIGN KEY ' . $foreignKey;
    }

    /**
     * Returns the SQL to drop a unique constraint.
     */
    public function getDropUniqueConstraintSQL(string $name, string $tableName): string
    {
        return $this->getDropConstraintSQL($name, $tableName);
    }

    /**
     * Returns the SQL statement(s) to create a table with the specified name, columns and constraints
     * on this platform.
     *
     * @param int $createFlags
     * @phpstan-param int-mask-of<self::CREATE_*> $createFlags
     *
     * @return list<string> The list of SQL statements.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getCreateTableSQL(Table $table, $createFlags = self::CREATE_INDEXES)
    {
        if (! is_int($createFlags)) {
            throw new InvalidArgumentException(
                'Second argument of AbstractPlatform::getCreateTableSQL() has to be integer.',
            );
        }

        if (($createFlags & self::CREATE_INDEXES) === 0) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5416',
                'Unsetting the CREATE_INDEXES flag in AbstractPlatform::getCreateTableSQL() is deprecated.',
            );
        }

        if (($createFlags & self::CREATE_FOREIGNKEYS) === 0) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5416',
                'Not setting the CREATE_FOREIGNKEYS flag in AbstractPlatform::getCreateTableSQL()'
                    . ' is deprecated. In order to build the statements that create multiple tables'
                    . ' referencing each other via foreign keys, use AbstractPlatform::getCreateTablesSQL().',
            );
        }

        return $this->buildCreateTableSQL(
            $table,
            ($createFlags & self::CREATE_INDEXES) > 0,
            ($createFlags & self::CREATE_FOREIGNKEYS) > 0,
        );
    }

    public function createSelectSQLBuilder(): SelectSQLBuilder
    {
        return new DefaultSelectSQLBuilder($this, 'FOR UPDATE', 'SKIP LOCKED');
    }

    /**
     * @internal
     *
     * @return list<string>
     *
     * @throws Exception
     */
    final protected function getCreateTableWithoutForeignKeysSQL(Table $table): array
    {
        return $this->buildCreateTableSQL($table, true, false);
    }

    /**
     * @return list<string>
     *
     * @throws Exception
     */
    private function buildCreateTableSQL(Table $table, bool $createIndexes, bool $createForeignKeys): array
    {
        if (count($table->getColumns()) === 0) {
            throw Exception::noColumnsSpecifiedForTable($table->getName());
        }

        $tableName                    = $table->getQuotedName($this);
        $options                      = $table->getOptions();
        $options['uniqueConstraints'] = [];
        $options['indexes']           = [];
        $options['primary']           = [];

        if ($createIndexes) {
            foreach ($table->getIndexes() as $index) {
                if (! $index->isPrimary()) {
                    $options['indexes'][$index->getQuotedName($this)] = $index;

                    continue;
                }

                $options['primary']       = $index->getQuotedColumns($this);
                $options['primary_index'] = $index;
            }

            foreach ($table->getUniqueConstraints() as $uniqueConstraint) {
                $options['uniqueConstraints'][$uniqueConstraint->getQuotedName($this)] = $uniqueConstraint;
            }
        }

        if ($createForeignKeys) {
            $options['foreignKeys'] = [];

            foreach ($table->getForeignKeys() as $fkConstraint) {
                $options['foreignKeys'][] = $fkConstraint;
            }
        }

        $columnSql = [];
        $columns   = [];

        foreach ($table->getColumns() as $column) {
            if (
                $this->_eventManager !== null
                && $this->_eventManager->hasListeners(Events::onSchemaCreateTableColumn)
            ) {
                Deprecation::trigger(
                    'doctrine/dbal',
                    'https://github.com/doctrine/dbal/issues/5784',
                    'Subscribing to %s events is deprecated.',
                    Events::onSchemaCreateTableColumn,
                );

                $eventArgs = new SchemaCreateTableColumnEventArgs($column, $table, $this);

                $this->_eventManager->dispatchEvent(Events::onSchemaCreateTableColumn, $eventArgs);

                $columnSql = array_merge($columnSql, $eventArgs->getSql());

                if ($eventArgs->isDefaultPrevented()) {
                    continue;
                }
            }

            $columnData = $this->columnToArray($column);

            if (in_array($column->getName(), $options['primary'], true)) {
                $columnData['primary'] = true;
            }

            $columns[$columnData['name']] = $columnData;
        }

        if ($this->_eventManager !== null && $this->_eventManager->hasListeners(Events::onSchemaCreateTable)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/5784',
                'Subscribing to %s events is deprecated.',
                Events::onSchemaCreateTable,
            );

            $eventArgs = new SchemaCreateTableEventArgs($table, $columns, $options, $this);

            $this->_eventManager->dispatchEvent(Events::onSchemaCreateTable, $eventArgs);

            if ($eventArgs->isDefaultPrevented()) {
                return array_merge($eventArgs->getSql(), $columnSql);
            }
        }

        $sql = $this->_getCreateTableSQL($tableName, $columns, $options);

        if ($this->supportsCommentOnStatement()) {
            if ($table->hasOption('comment')) {
                $sql[] = $this->getCommentOnTableSQL($tableName, $table->getOption('comment'));
            }

            foreach ($table->getColumns() as $column) {
                $comment = $this->getColumnComment($column);

                if ($comment === null || $comment === '') {
                    continue;
                }

                $sql[] = $this->getCommentOnColumnSQL($tableName, $column->getQuotedName($this), $comment);
            }
        }

        return array_merge($sql, $columnSql);
    }

    /**
     * @param Table[] $tables
     *
     * @return list<string>
     *
     * @throws Exception
     */
    public function getCreateTablesSQL(array $tables): array
    {
        $sql = [];

        foreach ($tables as $table) {
            $sql = array_merge($sql, $this->getCreateTableWithoutForeignKeysSQL($table));
        }

        foreach ($tables as $table) {
            foreach ($table->getForeignKeys() as $foreignKey) {
                $sql[] = $this->getCreateForeignKeySQL(
                    $foreignKey,
                    $table->getQuotedName($this),
                );
            }
        }

        return $sql;
    }

    /**
     * @param list<Table> $tables
     *
     * @return list<string>
     */
    public function getDropTablesSQL(array $tables): array
    {
        $sql = [];

        foreach ($tables as $table) {
            foreach ($table->getForeignKeys() as $foreignKey) {
                $sql[] = $this->getDropForeignKeySQL(
                    $foreignKey->getQuotedName($this),
                    $table->getQuotedName($this),
                );
            }
        }

        foreach ($tables as $table) {
            $sql[] = $this->getDropTableSQL($table->getQuotedName($this));
        }

        return $sql;
    }

    protected function getCommentOnTableSQL(string $tableName, ?string $comment): string
    {
        $tableName = new Identifier($tableName);

        return sprintf(
            'COMMENT ON TABLE %s IS %s',
            $tableName->getQuotedName($this),
            $this->quoteStringLiteral((string) $comment),
        );
    }

    /**
     * @param string      $tableName
     * @param string      $columnName
     * @param string|null $comment
     *
     * @return string
     */
    public function getCommentOnColumnSQL($tableName, $columnName, $comment)
    {
        $tableName  = new Identifier($tableName);
        $columnName = new Identifier($columnName);

        return sprintf(
            'COMMENT ON COLUMN %s.%s IS %s',
            $tableName->getQuotedName($this),
            $columnName->getQuotedName($this),
            $this->quoteStringLiteral((string) $comment),
        );
    }

    /**
     * Returns the SQL to create inline comment on a column.
     *
     * @param string $comment
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getInlineColumnCommentSQL($comment)
    {
        if (! $this->supportsInlineColumnComments()) {
            throw Exception::notSupported(__METHOD__);
        }

        return 'COMMENT ' . $this->quoteStringLiteral($comment);
    }

    /**
     * Returns the SQL used to create a table.
     *
     * @param string    $name
     * @param mixed[][] $columns
     * @param mixed[]   $options
     *
     * @return string[]
     */
    protected function _getCreateTableSQL($name, array $columns, array $options = [])
    {
        $columnListSql = $this->getColumnDeclarationListSQL($columns);

        if (isset($options['uniqueConstraints']) && ! empty($options['uniqueConstraints'])) {
            foreach ($options['uniqueConstraints'] as $index => $definition) {
                $columnListSql .= ', ' . $this->getUniqueConstraintDeclarationSQL($index, $definition);
            }
        }

        if (isset($options['primary']) && ! empty($options['primary'])) {
            $columnListSql .= ', PRIMARY KEY(' . implode(', ', array_unique(array_values($options['primary']))) . ')';
        }

        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach ($options['indexes'] as $index => $definition) {
                $columnListSql .= ', ' . $this->getIndexDeclarationSQL($index, $definition);
            }
        }

        $query = 'CREATE TABLE ' . $name . ' (' . $columnListSql;
        $check = $this->getCheckDeclarationSQL($columns);

        if (! empty($check)) {
            $query .= ', ' . $check;
        }

        $query .= ')';

        $sql = [$query];

        if (isset($options['foreignKeys'])) {
            foreach ($options['foreignKeys'] as $definition) {
                $sql[] = $this->getCreateForeignKeySQL($definition, $name);
            }
        }

        return $sql;
    }

    /** @return string */
    public function getCreateTemporaryTableSnippetSQL()
    {
        return 'CREATE TEMPORARY TABLE';
    }

    /**
     * Generates SQL statements that can be used to apply the diff.
     *
     * @return list<string>
     */
    public function getAlterSchemaSQL(SchemaDiff $diff): array
    {
        return $diff->toSql($this);
    }

    /**
     * Returns the SQL to create a sequence on this platform.
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getCreateSequenceSQL(Sequence $sequence)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * Returns the SQL to change a sequence on this platform.
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getAlterSequenceSQL(Sequence $sequence)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * Returns the SQL snippet to drop an existing sequence.
     *
     * @param Sequence|string $sequence
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDropSequenceSQL($sequence)
    {
        if (! $this->supportsSequences()) {
            throw Exception::notSupported(__METHOD__);
        }

        if ($sequence instanceof Sequence) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $sequence as a Sequence object to %s is deprecated. Pass it as a quoted name instead.',
                __METHOD__,
            );

            $sequence = $sequence->getQuotedName($this);
        }

        return 'DROP SEQUENCE ' . $sequence;
    }

    /**
     * Returns the SQL to create a constraint on a table on this platform.
     *
     * @deprecated Use {@see getCreateIndexSQL()}, {@see getCreateForeignKeySQL()}
     *             or {@see getCreateUniqueConstraintSQL()} instead.
     *
     * @param Table|string $table
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getCreateConstraintSQL(Constraint $constraint, $table)
    {
        if ($table instanceof Table) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $table as a Table object to %s is deprecated. Pass it as a quoted name instead.',
                __METHOD__,
            );

            $table = $table->getQuotedName($this);
        }

        $query = 'ALTER TABLE ' . $table . ' ADD CONSTRAINT ' . $constraint->getQuotedName($this);

        $columnList = '(' . implode(', ', $constraint->getQuotedColumns($this)) . ')';

        $referencesClause = '';
        if ($constraint instanceof Index) {
            if ($constraint->isPrimary()) {
                $query .= ' PRIMARY KEY';
            } elseif ($constraint->isUnique()) {
                $query .= ' UNIQUE';
            } else {
                throw new InvalidArgumentException(
                    'Can only create primary or unique constraints, no common indexes with getCreateConstraintSQL().',
                );
            }
        } elseif ($constraint instanceof UniqueConstraint) {
            $query .= ' UNIQUE';
        } elseif ($constraint instanceof ForeignKeyConstraint) {
            $query .= ' FOREIGN KEY';

            $referencesClause = ' REFERENCES ' . $constraint->getQuotedForeignTableName($this) .
                ' (' . implode(', ', $constraint->getQuotedForeignColumns($this)) . ')';
        }

        $query .= ' ' . $columnList . $referencesClause;

        return $query;
    }

    /**
     * Returns the SQL to create an index on a table on this platform.
     *
     * @param Table|string $table The name of the table on which the index is to be created.
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getCreateIndexSQL(Index $index, $table)
    {
        if ($table instanceof Table) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $table as a Table object to %s is deprecated. Pass it as a quoted name instead.',
                __METHOD__,
            );

            $table = $table->getQuotedName($this);
        }

        $name    = $index->getQuotedName($this);
        $columns = $index->getColumns();

        if (count($columns) === 0) {
            throw new InvalidArgumentException(sprintf(
                'Incomplete or invalid index definition %s on table %s',
                $name,
                $table,
            ));
        }

        if ($index->isPrimary()) {
            return $this->getCreatePrimaryKeySQL($index, $table);
        }

        $query  = 'CREATE ' . $this->getCreateIndexSQLFlags($index) . 'INDEX ' . $name . ' ON ' . $table;
        $query .= ' (' . $this->getIndexFieldDeclarationListSQL($index) . ')' . $this->getPartialIndexSQL($index);

        return $query;
    }

    /**
     * Adds condition for partial index.
     *
     * @return string
     */
    protected function getPartialIndexSQL(Index $index)
    {
        if ($this->supportsPartialIndexes() && $index->hasOption('where')) {
            return ' WHERE ' . $index->getOption('where');
        }

        return '';
    }

    /**
     * Adds additional flags for index generation.
     *
     * @return string
     */
    protected function getCreateIndexSQLFlags(Index $index)
    {
        return $index->isUnique() ? 'UNIQUE ' : '';
    }

    /**
     * Returns the SQL to create an unnamed primary key constraint.
     *
     * @param Table|string $table
     *
     * @return string
     */
    public function getCreatePrimaryKeySQL(Index $index, $table)
    {
        if ($table instanceof Table) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $table as a Table object to %s is deprecated. Pass it as a quoted name instead.',
                __METHOD__,
            );

            $table = $table->getQuotedName($this);
        }

        return 'ALTER TABLE ' . $table . ' ADD PRIMARY KEY (' . $this->getIndexFieldDeclarationListSQL($index) . ')';
    }

    /**
     * Returns the SQL to create a named schema.
     *
     * @param string $schemaName
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getCreateSchemaSQL($schemaName)
    {
        if (! $this->supportsSchemas()) {
            throw Exception::notSupported(__METHOD__);
        }

        return 'CREATE SCHEMA ' . $schemaName;
    }

    /**
     * Returns the SQL to create a unique constraint on a table on this platform.
     */
    public function getCreateUniqueConstraintSQL(UniqueConstraint $constraint, string $tableName): string
    {
        return $this->getCreateConstraintSQL($constraint, $tableName);
    }

    /**
     * Returns the SQL snippet to drop a schema.
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDropSchemaSQL(string $schemaName): string
    {
        if (! $this->supportsSchemas()) {
            throw Exception::notSupported(__METHOD__);
        }

        return 'DROP SCHEMA ' . $schemaName;
    }

    /**
     * Quotes a string so that it can be safely used as a table or column name,
     * even if it is a reserved word of the platform. This also detects identifier
     * chains separated by dot and quotes them independently.
     *
     * NOTE: Just because you CAN use quoted identifiers doesn't mean
     * you SHOULD use them. In general, they end up causing way more
     * problems than they solve.
     *
     * @param string $str The identifier name to be quoted.
     *
     * @return string The quoted identifier string.
     */
    public function quoteIdentifier($str)
    {
        if (strpos($str, '.') !== false) {
            $parts = array_map([$this, 'quoteSingleIdentifier'], explode('.', $str));

            return implode('.', $parts);
        }

        return $this->quoteSingleIdentifier($str);
    }

    /**
     * Quotes a single identifier (no dot chain separation).
     *
     * @param string $str The identifier name to be quoted.
     *
     * @return string The quoted identifier string.
     */
    public function quoteSingleIdentifier($str)
    {
        $c = $this->getIdentifierQuoteCharacter();

        return $c . str_replace($c, $c . $c, $str) . $c;
    }

    /**
     * Returns the SQL to create a new foreign key.
     *
     * @param ForeignKeyConstraint $foreignKey The foreign key constraint.
     * @param Table|string         $table      The name of the table on which the foreign key is to be created.
     *
     * @return string
     */
    public function getCreateForeignKeySQL(ForeignKeyConstraint $foreignKey, $table)
    {
        if ($table instanceof Table) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $table as a Table object to %s is deprecated. Pass it as a quoted name instead.',
                __METHOD__,
            );

            $table = $table->getQuotedName($this);
        }

        return 'ALTER TABLE ' . $table . ' ADD ' . $this->getForeignKeyDeclarationSQL($foreignKey);
    }

    /**
     * Gets the SQL statements for altering an existing table.
     *
     * This method returns an array of SQL statements, since some platforms need several statements.
     *
     * @return list<string>
     *
     * @throws Exception If not supported on this platform.
     */
    public function getAlterTableSQL(TableDiff $diff)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /** @return list<string> */
    public function getRenameTableSQL(string $oldName, string $newName): array
    {
        return [
            sprintf('ALTER TABLE %s RENAME TO %s', $oldName, $newName),
        ];
    }

    /**
     * @param mixed[] $columnSql
     *
     * @return bool
     */
    protected function onSchemaAlterTableAddColumn(Column $column, TableDiff $diff, &$columnSql)
    {
        if ($this->_eventManager === null) {
            return false;
        }

        if (! $this->_eventManager->hasListeners(Events::onSchemaAlterTableAddColumn)) {
            return false;
        }

        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/5784',
            'Subscribing to %s events is deprecated.',
            Events::onSchemaAlterTableAddColumn,
        );

        $eventArgs = new SchemaAlterTableAddColumnEventArgs($column, $diff, $this);
        $this->_eventManager->dispatchEvent(Events::onSchemaAlterTableAddColumn, $eventArgs);

        $columnSql = array_merge($columnSql, $eventArgs->getSql());

        return $eventArgs->isDefaultPrevented();
    }

    /**
     * @param string[] $columnSql
     *
     * @return bool
     */
    protected function onSchemaAlterTableRemoveColumn(Column $column, TableDiff $diff, &$columnSql)
    {
        if ($this->_eventManager === null) {
            return false;
        }

        if (! $this->_eventManager->hasListeners(Events::onSchemaAlterTableRemoveColumn)) {
            return false;
        }

        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/5784',
            'Subscribing to %s events is deprecated.',
            Events::onSchemaAlterTableRemoveColumn,
        );

        $eventArgs = new SchemaAlterTableRemoveColumnEventArgs($column, $diff, $this);
        $this->_eventManager->dispatchEvent(Events::onSchemaAlterTableRemoveColumn, $eventArgs);

        $columnSql = array_merge($columnSql, $eventArgs->getSql());

        return $eventArgs->isDefaultPrevented();
    }

    /**
     * @param string[] $columnSql
     *
     * @return bool
     */
    protected function onSchemaAlterTableChangeColumn(ColumnDiff $columnDiff, TableDiff $diff, &$columnSql)
    {
        if ($this->_eventManager === null) {
            return false;
        }

        if (! $this->_eventManager->hasListeners(Events::onSchemaAlterTableChangeColumn)) {
            return false;
        }

        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/5784',
            'Subscribing to %s events is deprecated.',
            Events::onSchemaAlterTableChangeColumn,
        );

        $eventArgs = new SchemaAlterTableChangeColumnEventArgs($columnDiff, $diff, $this);
        $this->_eventManager->dispatchEvent(Events::onSchemaAlterTableChangeColumn, $eventArgs);

        $columnSql = array_merge($columnSql, $eventArgs->getSql());

        return $eventArgs->isDefaultPrevented();
    }

    /**
     * @param string   $oldColumnName
     * @param string[] $columnSql
     *
     * @return bool
     */
    protected function onSchemaAlterTableRenameColumn($oldColumnName, Column $column, TableDiff $diff, &$columnSql)
    {
        if ($this->_eventManager === null) {
            return false;
        }

        if (! $this->_eventManager->hasListeners(Events::onSchemaAlterTableRenameColumn)) {
            return false;
        }

        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/5784',
            'Subscribing to %s events is deprecated.',
            Events::onSchemaAlterTableRenameColumn,
        );

        $eventArgs = new SchemaAlterTableRenameColumnEventArgs($oldColumnName, $column, $diff, $this);
        $this->_eventManager->dispatchEvent(Events::onSchemaAlterTableRenameColumn, $eventArgs);

        $columnSql = array_merge($columnSql, $eventArgs->getSql());

        return $eventArgs->isDefaultPrevented();
    }

    /**
     * @param string[] $sql
     *
     * @return bool
     */
    protected function onSchemaAlterTable(TableDiff $diff, &$sql)
    {
        if ($this->_eventManager === null) {
            return false;
        }

        if (! $this->_eventManager->hasListeners(Events::onSchemaAlterTable)) {
            return false;
        }

        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/5784',
            'Subscribing to %s events is deprecated.',
            Events::onSchemaAlterTable,
        );

        $eventArgs = new SchemaAlterTableEventArgs($diff, $this);
        $this->_eventManager->dispatchEvent(Events::onSchemaAlterTable, $eventArgs);

        $sql = array_merge($sql, $eventArgs->getSql());

        return $eventArgs->isDefaultPrevented();
    }

    /** @return string[] */
    protected function getPreAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        $tableNameSQL = ($diff->getOldTable() ?? $diff->getName($this))->getQuotedName($this);

        $sql = [];
        if ($this->supportsForeignKeyConstraints()) {
            foreach ($diff->getDroppedForeignKeys() as $foreignKey) {
                if ($foreignKey instanceof ForeignKeyConstraint) {
                    $foreignKey = $foreignKey->getQuotedName($this);
                }

                $sql[] = $this->getDropForeignKeySQL($foreignKey, $tableNameSQL);
            }

            foreach ($diff->getModifiedForeignKeys() as $foreignKey) {
                $sql[] = $this->getDropForeignKeySQL($foreignKey->getQuotedName($this), $tableNameSQL);
            }
        }

        foreach ($diff->getDroppedIndexes() as $index) {
            $sql[] = $this->getDropIndexSQL($index->getQuotedName($this), $tableNameSQL);
        }

        foreach ($diff->getModifiedIndexes() as $index) {
            $sql[] = $this->getDropIndexSQL($index->getQuotedName($this), $tableNameSQL);
        }

        return $sql;
    }

    /** @return string[] */
    protected function getPostAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        $sql     = [];
        $newName = $diff->getNewName();

        if ($newName !== false) {
            $tableNameSQL = $newName->getQuotedName($this);
        } else {
            $tableNameSQL = ($diff->getOldTable() ?? $diff->getName($this))->getQuotedName($this);
        }

        if ($this->supportsForeignKeyConstraints()) {
            foreach ($diff->getAddedForeignKeys() as $foreignKey) {
                $sql[] = $this->getCreateForeignKeySQL($foreignKey, $tableNameSQL);
            }

            foreach ($diff->getModifiedForeignKeys() as $foreignKey) {
                $sql[] = $this->getCreateForeignKeySQL($foreignKey, $tableNameSQL);
            }
        }

        foreach ($diff->getAddedIndexes() as $index) {
            $sql[] = $this->getCreateIndexSQL($index, $tableNameSQL);
        }

        foreach ($diff->getModifiedIndexes() as $index) {
            $sql[] = $this->getCreateIndexSQL($index, $tableNameSQL);
        }

        foreach ($diff->getRenamedIndexes() as $oldIndexName => $index) {
            $oldIndexName = new Identifier($oldIndexName);
            $sql          = array_merge(
                $sql,
                $this->getRenameIndexSQL($oldIndexName->getQuotedName($this), $index, $tableNameSQL),
            );
        }

        return $sql;
    }

    /**
     * Returns the SQL for renaming an index on a table.
     *
     * @param string $oldIndexName The name of the index to rename from.
     * @param Index  $index        The definition of the index to rename to.
     * @param string $tableName    The table to rename the given index on.
     *
     * @return string[] The sequence of SQL statements for renaming the given index.
     */
    protected function getRenameIndexSQL($oldIndexName, Index $index, $tableName)
    {
        return [
            $this->getDropIndexSQL($oldIndexName, $tableName),
            $this->getCreateIndexSQL($index, $tableName),
        ];
    }

    /**
     * Gets declaration of a number of columns in bulk.
     *
     * @param mixed[][] $columns A multidimensional associative array.
     *                           The first dimension determines the column name, while the second
     *                           dimension is keyed with the name of the properties
     *                           of the column being declared as array indexes. Currently, the types
     *                           of supported column properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the text
     *          column. If this argument is missing the column should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      default
     *          Text value to be used as default for this column.
     *
     *      notnull
     *          Boolean flag that indicates whether this column is constrained
     *          to not be set to null.
     *      charset
     *          Text value with the default CHARACTER SET for this column.
     *      collation
     *          Text value with the default COLLATION for this column.
     *      unique
     *          unique constraint
     *
     * @return string
     */
    public function getColumnDeclarationListSQL(array $columns)
    {
        $declarations = [];

        foreach ($columns as $name => $column) {
            $declarations[] = $this->getColumnDeclarationSQL($name, $column);
        }

        return implode(', ', $declarations);
    }

    /**
     * Obtains DBMS specific SQL code portion needed to declare a generic type
     * column to be used in statements like CREATE TABLE.
     *
     * @param string  $name   The name the column to be declared.
     * @param mixed[] $column An associative array with the name of the properties
     *                        of the column being declared as array indexes. Currently, the types
     *                        of supported column properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the text
     *          column. If this argument is missing the column should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      default
     *          Text value to be used as default for this column.
     *
     *      notnull
     *          Boolean flag that indicates whether this column is constrained
     *          to not be set to null.
     *      charset
     *          Text value with the default CHARACTER SET for this column.
     *      collation
     *          Text value with the default COLLATION for this column.
     *      unique
     *          unique constraint
     *      check
     *          column check constraint
     *      columnDefinition
     *          a string that defines the complete column
     *
     * @return string DBMS specific SQL code portion that should be used to declare the column.
     *
     * @throws Exception
     */
    public function getColumnDeclarationSQL($name, array $column)
    {
        if (isset($column['columnDefinition'])) {
            $declaration = $this->getCustomTypeDeclarationSQL($column);
        } else {
            $default = $this->getDefaultValueDeclarationSQL($column);

            $charset = ! empty($column['charset']) ?
                ' ' . $this->getColumnCharsetDeclarationSQL($column['charset']) : '';

            $collation = ! empty($column['collation']) ?
                ' ' . $this->getColumnCollationDeclarationSQL($column['collation']) : '';

            $notnull = ! empty($column['notnull']) ? ' NOT NULL' : '';

            if (! empty($column['unique'])) {
                Deprecation::trigger(
                    'doctrine/dbal',
                    'https://github.com/doctrine/dbal/pull/5656',
                    'The usage of the "unique" column property is deprecated. Use unique constraints instead.',
                );

                $unique = ' ' . $this->getUniqueFieldDeclarationSQL();
            } else {
                $unique = '';
            }

            if (! empty($column['check'])) {
                Deprecation::trigger(
                    'doctrine/dbal',
                    'https://github.com/doctrine/dbal/pull/5656',
                    'The usage of the "check" column property is deprecated.',
                );

                $check = ' ' . $column['check'];
            } else {
                $check = '';
            }

            $typeDecl    = $column['type']->getSQLDeclaration($column, $this);
            $declaration = $typeDecl . $charset . $default . $notnull . $unique . $check . $collation;

            if ($this->supportsInlineColumnComments() && isset($column['comment']) && $column['comment'] !== '') {
                $declaration .= ' ' . $this->getInlineColumnCommentSQL($column['comment']);
            }
        }

        return $name . ' ' . $declaration;
    }

    /**
     * Returns the SQL snippet that declares a floating point column of arbitrary precision.
     *
     * @param mixed[] $column
     *
     * @return string
     */
    public function getDecimalTypeDeclarationSQL(array $column)
    {
        if (empty($column['precision'])) {
            if (! isset($column['precision'])) {
                Deprecation::trigger(
                    'doctrine/dbal',
                    'https://github.com/doctrine/dbal/pull/5637',
                    'Relying on the default decimal column precision is deprecated'
                        . ', specify the precision explicitly.',
                );
            }

            $precision = 10;
        } else {
            $precision = $column['precision'];
        }

        if (empty($column['scale'])) {
            if (! isset($column['scale'])) {
                Deprecation::trigger(
                    'doctrine/dbal',
                    'https://github.com/doctrine/dbal/pull/5637',
                    'Relying on the default decimal column scale is deprecated'
                        . ', specify the scale explicitly.',
                );
            }

            $scale = 0;
        } else {
            $scale = $column['scale'];
        }

        return 'NUMERIC(' . $precision . ', ' . $scale . ')';
    }

    /**
     * Obtains DBMS specific SQL code portion needed to set a default value
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param mixed[] $column The column definition array.
     *
     * @return string DBMS specific SQL code portion needed to set a default value.
     */
    public function getDefaultValueDeclarationSQL($column)
    {
        if (! isset($column['default'])) {
            return empty($column['notnull']) ? ' DEFAULT NULL' : '';
        }

        $default = $column['default'];

        if (! isset($column['type'])) {
            return " DEFAULT '" . $default . "'";
        }

        $type = $column['type'];

        if ($type instanceof Types\PhpIntegerMappingType) {
            return ' DEFAULT ' . $default;
        }

        if ($type instanceof Types\PhpDateTimeMappingType && $default === $this->getCurrentTimestampSQL()) {
            return ' DEFAULT ' . $this->getCurrentTimestampSQL();
        }

        if ($type instanceof Types\TimeType && $default === $this->getCurrentTimeSQL()) {
            return ' DEFAULT ' . $this->getCurrentTimeSQL();
        }

        if ($type instanceof Types\DateType && $default === $this->getCurrentDateSQL()) {
            return ' DEFAULT ' . $this->getCurrentDateSQL();
        }

        if ($type instanceof Types\BooleanType) {
            return ' DEFAULT ' . $this->convertBooleans($default);
        }

        return ' DEFAULT ' . $this->quoteStringLiteral($default);
    }

    /**
     * Obtains DBMS specific SQL code portion needed to set a CHECK constraint
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param string[]|mixed[][] $definition The check definition.
     *
     * @return string DBMS specific SQL code portion needed to set a CHECK constraint.
     */
    public function getCheckDeclarationSQL(array $definition)
    {
        $constraints = [];
        foreach ($definition as $column => $def) {
            if (is_string($def)) {
                $constraints[] = 'CHECK (' . $def . ')';
            } else {
                if (isset($def['min'])) {
                    $constraints[] = 'CHECK (' . $column . ' >= ' . $def['min'] . ')';
                }

                if (isset($def['max'])) {
                    $constraints[] = 'CHECK (' . $column . ' <= ' . $def['max'] . ')';
                }
            }
        }

        return implode(', ', $constraints);
    }

    /**
     * Obtains DBMS specific SQL code portion needed to set a unique
     * constraint declaration to be used in statements like CREATE TABLE.
     *
     * @param string           $name       The name of the unique constraint.
     * @param UniqueConstraint $constraint The unique constraint definition.
     *
     * @return string DBMS specific SQL code portion needed to set a constraint.
     *
     * @throws InvalidArgumentException
     */
    public function getUniqueConstraintDeclarationSQL($name, UniqueConstraint $constraint)
    {
        $columns = $constraint->getQuotedColumns($this);
        $name    = new Identifier($name);

        if (count($columns) === 0) {
            throw new InvalidArgumentException("Incomplete definition. 'columns' required.");
        }

        $constraintFlags = array_merge(['UNIQUE'], array_map('strtoupper', $constraint->getFlags()));
        $constraintName  = $name->getQuotedName($this);
        $columnListNames = $this->getColumnsFieldDeclarationListSQL($columns);

        return sprintf('CONSTRAINT %s %s (%s)', $constraintName, implode(' ', $constraintFlags), $columnListNames);
    }

    /**
     * Obtains DBMS specific SQL code portion needed to set an index
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param string $name  The name of the index.
     * @param Index  $index The index definition.
     *
     * @return string DBMS specific SQL code portion needed to set an index.
     *
     * @throws InvalidArgumentException
     */
    public function getIndexDeclarationSQL($name, Index $index)
    {
        $columns = $index->getColumns();
        $name    = new Identifier($name);

        if (count($columns) === 0) {
            throw new InvalidArgumentException("Incomplete definition. 'columns' required.");
        }

        return $this->getCreateIndexSQLFlags($index) . 'INDEX ' . $name->getQuotedName($this)
            . ' (' . $this->getIndexFieldDeclarationListSQL($index) . ')' . $this->getPartialIndexSQL($index);
    }

    /**
     * Obtains SQL code portion needed to create a custom column,
     * e.g. when a column has the "columnDefinition" keyword.
     * Only "AUTOINCREMENT" and "PRIMARY KEY" are added if appropriate.
     *
     * @deprecated
     *
     * @param mixed[] $column
     *
     * @return string
     */
    public function getCustomTypeDeclarationSQL(array $column)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5527',
            '%s is deprecated.',
            __METHOD__,
        );

        return $column['columnDefinition'];
    }

    /**
     * Obtains DBMS specific SQL code portion needed to set an index
     * declaration to be used in statements like CREATE TABLE.
     *
     * @deprecated
     */
    public function getIndexFieldDeclarationListSQL(Index $index): string
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5527',
            '%s is deprecated.',
            __METHOD__,
        );

        return implode(', ', $index->getQuotedColumns($this));
    }

    /**
     * Obtains DBMS specific SQL code portion needed to set an index
     * declaration to be used in statements like CREATE TABLE.
     *
     * @deprecated
     *
     * @param mixed[] $columns
     */
    public function getColumnsFieldDeclarationListSQL(array $columns): string
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5527',
            '%s is deprecated.',
            __METHOD__,
        );

        $ret = [];

        foreach ($columns as $column => $definition) {
            if (is_array($definition)) {
                $ret[] = $column;
            } else {
                $ret[] = $definition;
            }
        }

        return implode(', ', $ret);
    }

    /**
     * Returns the required SQL string that fits between CREATE ... TABLE
     * to create the table as a temporary table.
     *
     * Should be overridden in driver classes to return the correct string for the
     * specific database type.
     *
     * The default is to return the string "TEMPORARY" - this will result in a
     * SQL error for any database that does not support temporary tables, or that
     * requires a different SQL command from "CREATE TEMPORARY TABLE".
     *
     * @deprecated
     *
     * @return string The string required to be placed between "CREATE" and "TABLE"
     *                to generate a temporary table, if possible.
     */
    public function getTemporaryTableSQL()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getTemporaryTableSQL() is deprecated.',
        );

        return 'TEMPORARY';
    }

    /**
     * Some vendors require temporary table names to be qualified specially.
     *
     * @param string $tableName
     *
     * @return string
     */
    public function getTemporaryTableName($tableName)
    {
        return $tableName;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set the FOREIGN KEY constraint
     * of a column declaration to be used in statements like CREATE TABLE.
     *
     * @return string DBMS specific SQL code portion needed to set the FOREIGN KEY constraint
     *                of a column declaration.
     */
    public function getForeignKeyDeclarationSQL(ForeignKeyConstraint $foreignKey)
    {
        $sql  = $this->getForeignKeyBaseDeclarationSQL($foreignKey);
        $sql .= $this->getAdvancedForeignKeyOptionsSQL($foreignKey);

        return $sql;
    }

    /**
     * Returns the FOREIGN KEY query section dealing with non-standard options
     * as MATCH, INITIALLY DEFERRED, ON UPDATE, ...
     *
     * @param ForeignKeyConstraint $foreignKey The foreign key definition.
     *
     * @return string
     */
    public function getAdvancedForeignKeyOptionsSQL(ForeignKeyConstraint $foreignKey)
    {
        $query = '';
        if ($foreignKey->hasOption('onUpdate')) {
            $query .= ' ON UPDATE ' . $this->getForeignKeyReferentialActionSQL($foreignKey->getOption('onUpdate'));
        }

        if ($foreignKey->hasOption('onDelete')) {
            $query .= ' ON DELETE ' . $this->getForeignKeyReferentialActionSQL($foreignKey->getOption('onDelete'));
        }

        return $query;
    }

    /**
     * Returns the given referential action in uppercase if valid, otherwise throws an exception.
     *
     * @param string $action The foreign key referential action.
     *
     * @return string
     *
     * @throws InvalidArgumentException If unknown referential action given.
     */
    public function getForeignKeyReferentialActionSQL($action)
    {
        $upper = strtoupper($action);
        switch ($upper) {
            case 'CASCADE':
            case 'SET NULL':
            case 'NO ACTION':
            case 'RESTRICT':
            case 'SET DEFAULT':
                return $upper;

            default:
                throw new InvalidArgumentException('Invalid foreign key action: ' . $upper);
        }
    }

    /**
     * Obtains DBMS specific SQL code portion needed to set the FOREIGN KEY constraint
     * of a column declaration to be used in statements like CREATE TABLE.
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getForeignKeyBaseDeclarationSQL(ForeignKeyConstraint $foreignKey)
    {
        $sql = '';
        if (strlen($foreignKey->getName()) > 0) {
            $sql .= 'CONSTRAINT ' . $foreignKey->getQuotedName($this) . ' ';
        }

        $sql .= 'FOREIGN KEY (';

        if (count($foreignKey->getLocalColumns()) === 0) {
            throw new InvalidArgumentException("Incomplete definition. 'local' required.");
        }

        if (count($foreignKey->getForeignColumns()) === 0) {
            throw new InvalidArgumentException("Incomplete definition. 'foreign' required.");
        }

        if (strlen($foreignKey->getForeignTableName()) === 0) {
            throw new InvalidArgumentException("Incomplete definition. 'foreignTable' required.");
        }

        return $sql . implode(', ', $foreignKey->getQuotedLocalColumns($this))
            . ') REFERENCES '
            . $foreignKey->getQuotedForeignTableName($this) . ' ('
            . implode(', ', $foreignKey->getQuotedForeignColumns($this)) . ')';
    }

    /**
     * Obtains DBMS specific SQL code portion needed to set the UNIQUE constraint
     * of a column declaration to be used in statements like CREATE TABLE.
     *
     * @deprecated Use UNIQUE in SQL instead.
     *
     * @return string DBMS specific SQL code portion needed to set the UNIQUE constraint
     *                of a column declaration.
     */
    public function getUniqueFieldDeclarationSQL()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getUniqueFieldDeclarationSQL() is deprecated. Use UNIQUE in SQL instead.',
        );

        return 'UNIQUE';
    }

    /**
     * Obtains DBMS specific SQL code portion needed to set the CHARACTER SET
     * of a column declaration to be used in statements like CREATE TABLE.
     *
     * @param string $charset The name of the charset.
     *
     * @return string DBMS specific SQL code portion needed to set the CHARACTER SET
     *                of a column declaration.
     */
    public function getColumnCharsetDeclarationSQL($charset)
    {
        return '';
    }

    /**
     * Obtains DBMS specific SQL code portion needed to set the COLLATION
     * of a column declaration to be used in statements like CREATE TABLE.
     *
     * @param string $collation The name of the collation.
     *
     * @return string DBMS specific SQL code portion needed to set the COLLATION
     *                of a column declaration.
     */
    public function getColumnCollationDeclarationSQL($collation)
    {
        return $this->supportsColumnCollation() ? 'COLLATE ' . $this->quoteSingleIdentifier($collation) : '';
    }

    /**
     * Whether the platform prefers identity columns (eg. autoincrement) for ID generation.
     * Subclasses should override this method to return TRUE if they prefer identity columns.
     *
     * @deprecated
     *
     * @return bool
     */
    public function prefersIdentityColumns()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/1519',
            'AbstractPlatform::prefersIdentityColumns() is deprecated.',
        );

        return false;
    }

    /**
     * Some platforms need the boolean values to be converted.
     *
     * The default conversion in this implementation converts to integers (false => 0, true => 1).
     *
     * Note: if the input is not a boolean the original input might be returned.
     *
     * There are two contexts when converting booleans: Literals and Prepared Statements.
     * This method should handle the literal case
     *
     * @param mixed $item A boolean or an array of them.
     *
     * @return mixed A boolean database value or an array of them.
     */
    public function convertBooleans($item)
    {
        if (is_array($item)) {
            foreach ($item as $k => $value) {
                if (! is_bool($value)) {
                    continue;
                }

                $item[$k] = (int) $value;
            }
        } elseif (is_bool($item)) {
            $item = (int) $item;
        }

        return $item;
    }

    /**
     * Some platforms have boolean literals that needs to be correctly converted
     *
     * The default conversion tries to convert value into bool "(bool)$item"
     *
     * @param T $item
     *
     * @return (T is null ? null : bool)
     *
     * @template T
     */
    public function convertFromBoolean($item)
    {
        return $item === null ? null : (bool) $item;
    }

    /**
     * This method should handle the prepared statements case. When there is no
     * distinction, it's OK to use the same method.
     *
     * Note: if the input is not a boolean the original input might be returned.
     *
     * @param mixed $item A boolean or an array of them.
     *
     * @return mixed A boolean database value or an array of them.
     */
    public function convertBooleansToDatabaseValue($item)
    {
        return $this->convertBooleans($item);
    }

    /**
     * Returns the SQL specific for the platform to get the current date.
     *
     * @return string
     */
    public function getCurrentDateSQL()
    {
        return 'CURRENT_DATE';
    }

    /**
     * Returns the SQL specific for the platform to get the current time.
     *
     * @return string
     */
    public function getCurrentTimeSQL()
    {
        return 'CURRENT_TIME';
    }

    /**
     * Returns the SQL specific for the platform to get the current timestamp
     *
     * @return string
     */
    public function getCurrentTimestampSQL()
    {
        return 'CURRENT_TIMESTAMP';
    }

    /**
     * Returns the SQL for a given transaction isolation level Connection constant.
     *
     * @param int $level
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function _getTransactionIsolationLevelSQL($level)
    {
        switch ($level) {
            case TransactionIsolationLevel::READ_UNCOMMITTED:
                return 'READ UNCOMMITTED';

            case TransactionIsolationLevel::READ_COMMITTED:
                return 'READ COMMITTED';

            case TransactionIsolationLevel::REPEATABLE_READ:
                return 'REPEATABLE READ';

            case TransactionIsolationLevel::SERIALIZABLE:
                return 'SERIALIZABLE';

            default:
                throw new InvalidArgumentException('Invalid isolation level:' . $level);
        }
    }

    /**
     * @internal The method should be only used from within the {@see AbstractSchemaManager} class hierarchy.
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getListDatabasesSQL()
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * Returns the SQL statement for retrieving the namespaces defined in the database.
     *
     * @deprecated Use {@see AbstractSchemaManager::listSchemaNames()} instead.
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getListNamespacesSQL()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4503',
            'AbstractPlatform::getListNamespacesSQL() is deprecated,'
                . ' use AbstractSchemaManager::listSchemaNames() instead.',
        );

        throw Exception::notSupported(__METHOD__);
    }

    /**
     * @internal The method should be only used from within the {@see AbstractSchemaManager} class hierarchy.
     *
     * @param string $database
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getListSequencesSQL($database)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * @deprecated
     *
     * @param string $table
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getListTableConstraintsSQL($table)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * @param string $table
     * @param string $database
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getListTableColumnsSQL($table, $database = null)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getListTablesSQL()
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * @deprecated
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getListUsersSQL()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::getListUsersSQL() is deprecated.',
        );

        throw Exception::notSupported(__METHOD__);
    }

    /**
     * Returns the SQL to list all views of a database or user.
     *
     * @internal The method should be only used from within the {@see AbstractSchemaManager} class hierarchy.
     *
     * @param string $database
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getListViewsSQL($database)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * Returns the list of indexes for the current database.
     *
     * The current database parameter is optional but will always be passed
     * when using the SchemaManager API and is the database the given table is in.
     *
     * Attention: Some platforms only support currentDatabase when they
     * are connected with that database. Cross-database information schema
     * requests may be impossible.
     *
     * @param string $table
     * @param string $database
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getListTableIndexesSQL($table, $database = null)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * @param string $table
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getListTableForeignKeysSQL($table)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * @param string $name
     * @param string $sql
     *
     * @return string
     */
    public function getCreateViewSQL($name, $sql)
    {
        return 'CREATE VIEW ' . $name . ' AS ' . $sql;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getDropViewSQL($name)
    {
        return 'DROP VIEW ' . $name;
    }

    /**
     * @param string $sequence
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getSequenceNextValSQL($sequence)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * Returns the SQL to create a new database.
     *
     * @param string $name The name of the database that should be created.
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getCreateDatabaseSQL($name)
    {
        if (! $this->supportsCreateDropDatabase()) {
            throw Exception::notSupported(__METHOD__);
        }

        return 'CREATE DATABASE ' . $name;
    }

    /**
     * Returns the SQL snippet to drop an existing database.
     *
     * @param string $name The name of the database that should be dropped.
     *
     * @return string
     */
    public function getDropDatabaseSQL($name)
    {
        if (! $this->supportsCreateDropDatabase()) {
            throw Exception::notSupported(__METHOD__);
        }

        return 'DROP DATABASE ' . $name;
    }

    /**
     * Returns the SQL to set the transaction isolation level.
     *
     * @param int $level
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getSetTransactionIsolationSQL($level)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * Obtains DBMS specific SQL to be used to create datetime columns in
     * statements like CREATE TABLE.
     *
     * @param mixed[] $column
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateTimeTypeDeclarationSQL(array $column)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * Obtains DBMS specific SQL to be used to create datetime with timezone offset columns.
     *
     * @param mixed[] $column
     *
     * @return string
     */
    public function getDateTimeTzTypeDeclarationSQL(array $column)
    {
        return $this->getDateTimeTypeDeclarationSQL($column);
    }

    /**
     * Obtains DBMS specific SQL to be used to create date columns in statements
     * like CREATE TABLE.
     *
     * @param mixed[] $column
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDateTypeDeclarationSQL(array $column)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * Obtains DBMS specific SQL to be used to create time columns in statements
     * like CREATE TABLE.
     *
     * @param mixed[] $column
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getTimeTypeDeclarationSQL(array $column)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * @param mixed[] $column
     *
     * @return string
     */
    public function getFloatDeclarationSQL(array $column)
    {
        return 'DOUBLE PRECISION';
    }

    /**
     * Gets the default transaction isolation level of the platform.
     *
     * @see TransactionIsolationLevel
     *
     * @return TransactionIsolationLevel::* The default isolation level.
     */
    public function getDefaultTransactionIsolationLevel()
    {
        return TransactionIsolationLevel::READ_COMMITTED;
    }

    /* supports*() methods */

    /**
     * Whether the platform supports sequences.
     *
     * @return bool
     */
    public function supportsSequences()
    {
        return false;
    }

    /**
     * Whether the platform supports identity columns.
     *
     * Identity columns are columns that receive an auto-generated value from the
     * database on insert of a row.
     *
     * @return bool
     */
    public function supportsIdentityColumns()
    {
        return false;
    }

    /**
     * Whether the platform emulates identity columns through sequences.
     *
     * Some platforms that do not support identity columns natively
     * but support sequences can emulate identity columns by using
     * sequences.
     *
     * @deprecated
     *
     * @return bool
     */
    public function usesSequenceEmulatedIdentityColumns()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5513',
            '%s is deprecated.',
            __METHOD__,
        );

        return false;
    }

    /**
     * Returns the name of the sequence for a particular identity column in a particular table.
     *
     * @deprecated
     *
     * @see usesSequenceEmulatedIdentityColumns
     *
     * @param string $tableName  The name of the table to return the sequence name for.
     * @param string $columnName The name of the identity column in the table to return the sequence name for.
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getIdentitySequenceName($tableName, $columnName)
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * Whether the platform supports indexes.
     *
     * @deprecated
     *
     * @return bool
     */
    public function supportsIndexes()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::supportsIndexes() is deprecated.',
        );

        return true;
    }

    /**
     * Whether the platform supports partial indexes.
     *
     * @return bool
     */
    public function supportsPartialIndexes()
    {
        return false;
    }

    /**
     * Whether the platform supports indexes with column length definitions.
     */
    public function supportsColumnLengthIndexes(): bool
    {
        return false;
    }

    /**
     * Whether the platform supports altering tables.
     *
     * @deprecated All platforms must implement altering tables.
     *
     * @return bool
     */
    public function supportsAlterTable()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::supportsAlterTable() is deprecated. All platforms must implement altering tables.',
        );

        return true;
    }

    /**
     * Whether the platform supports transactions.
     *
     * @deprecated
     *
     * @return bool
     */
    public function supportsTransactions()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::supportsTransactions() is deprecated.',
        );

        return true;
    }

    /**
     * Whether the platform supports savepoints.
     *
     * @return bool
     */
    public function supportsSavepoints()
    {
        return true;
    }

    /**
     * Whether the platform supports releasing savepoints.
     *
     * @return bool
     */
    public function supportsReleaseSavepoints()
    {
        return $this->supportsSavepoints();
    }

    /**
     * Whether the platform supports primary key constraints.
     *
     * @deprecated
     *
     * @return bool
     */
    public function supportsPrimaryConstraints()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::supportsPrimaryConstraints() is deprecated.',
        );

        return true;
    }

    /**
     * Whether the platform supports foreign key constraints.
     *
     * @deprecated All platforms should support foreign key constraints.
     *
     * @return bool
     */
    public function supportsForeignKeyConstraints()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5409',
            'AbstractPlatform::supportsForeignKeyConstraints() is deprecated.',
        );

        return true;
    }

    /**
     * Whether the platform supports database schemas.
     *
     * @return bool
     */
    public function supportsSchemas()
    {
        return false;
    }

    /**
     * Whether this platform can emulate schemas.
     *
     * @deprecated
     *
     * Platforms that either support or emulate schemas don't automatically
     * filter a schema for the namespaced elements in {@see AbstractManager::introspectSchema()}.
     *
     * @return bool
     */
    public function canEmulateSchemas()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4805',
            'AbstractPlatform::canEmulateSchemas() is deprecated.',
        );

        return false;
    }

    /**
     * Returns the default schema name.
     *
     * @deprecated
     *
     * @return string
     *
     * @throws Exception If not supported on this platform.
     */
    public function getDefaultSchemaName()
    {
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * Whether this platform supports create database.
     *
     * Some databases don't allow to create and drop databases at all or only with certain tools.
     *
     * @deprecated
     *
     * @return bool
     */
    public function supportsCreateDropDatabase()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5513',
            '%s is deprecated.',
            __METHOD__,
        );

        return true;
    }

    /**
     * Whether the platform supports getting the affected rows of a recent update/delete type query.
     *
     * @deprecated
     *
     * @return bool
     */
    public function supportsGettingAffectedRows()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::supportsGettingAffectedRows() is deprecated.',
        );

        return true;
    }

    /**
     * Whether this platform support to add inline column comments as postfix.
     *
     * @return bool
     */
    public function supportsInlineColumnComments()
    {
        return false;
    }

    /**
     * Whether this platform support the proprietary syntax "COMMENT ON asset".
     *
     * @return bool
     */
    public function supportsCommentOnStatement()
    {
        return false;
    }

    /**
     * Does this platform have native guid type.
     *
     * @deprecated
     *
     * @return bool
     */
    public function hasNativeGuidType()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5509',
            '%s is deprecated.',
            __METHOD__,
        );

        return false;
    }

    /**
     * Does this platform have native JSON type.
     *
     * @deprecated
     *
     * @return bool
     */
    public function hasNativeJsonType()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5509',
            '%s is deprecated.',
            __METHOD__,
        );

        return false;
    }

    /**
     * Whether this platform supports views.
     *
     * @deprecated All platforms must implement support for views.
     *
     * @return bool
     */
    public function supportsViews()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::supportsViews() is deprecated. All platforms must implement support for views.',
        );

        return true;
    }

    /**
     * Does this platform support column collation?
     *
     * @return bool
     */
    public function supportsColumnCollation()
    {
        return false;
    }

    /**
     * Gets the format string, as accepted by the date() function, that describes
     * the format of a stored datetime value of this platform.
     *
     * @return string The format string.
     */
    public function getDateTimeFormatString()
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * Gets the format string, as accepted by the date() function, that describes
     * the format of a stored datetime with timezone value of this platform.
     *
     * @return string The format string.
     */
    public function getDateTimeTzFormatString()
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * Gets the format string, as accepted by the date() function, that describes
     * the format of a stored date value of this platform.
     *
     * @return string The format string.
     */
    public function getDateFormatString()
    {
        return 'Y-m-d';
    }

    /**
     * Gets the format string, as accepted by the date() function, that describes
     * the format of a stored time value of this platform.
     *
     * @return string The format string.
     */
    public function getTimeFormatString()
    {
        return 'H:i:s';
    }

    /**
     * Adds an driver-specific LIMIT clause to the query.
     *
     * @param string   $query
     * @param int|null $limit
     * @param int      $offset
     *
     * @throws Exception
     */
    final public function modifyLimitQuery($query, $limit, $offset = 0): string
    {
        if ($offset < 0) {
            throw new Exception(sprintf(
                'Offset must be a positive integer or zero, %d given',
                $offset,
            ));
        }

        if ($offset > 0 && ! $this->supportsLimitOffset()) {
            throw new Exception(sprintf(
                'Platform %s does not support offset values in limit queries.',
                $this->getName(),
            ));
        }

        if ($limit !== null) {
            $limit = (int) $limit;
        }

        return $this->doModifyLimitQuery($query, $limit, (int) $offset);
    }

    /**
     * Adds an platform-specific LIMIT clause to the query.
     *
     * @param string   $query
     * @param int|null $limit
     * @param int      $offset
     *
     * @return string
     */
    protected function doModifyLimitQuery($query, $limit, $offset)
    {
        if ($limit !== null) {
            $query .= sprintf(' LIMIT %d', $limit);
        }

        if ($offset > 0) {
            $query .= sprintf(' OFFSET %d', $offset);
        }

        return $query;
    }

    /**
     * Whether the database platform support offsets in modify limit clauses.
     *
     * @deprecated All platforms must implement support for offsets in modify limit clauses.
     *
     * @return bool
     */
    public function supportsLimitOffset()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4724',
            'AbstractPlatform::supportsViews() is deprecated.'
            . ' All platforms must implement support for offsets in modify limit clauses.',
        );

        return true;
    }

    /**
     * Maximum length of any given database identifier, like tables or column names.
     *
     * @return int
     */
    public function getMaxIdentifierLength()
    {
        return 63;
    }

    /**
     * Returns the insert SQL for an empty insert statement.
     *
     * @param string $quotedTableName
     * @param string $quotedIdentifierColumnName
     *
     * @return string
     */
    public function getEmptyIdentityInsertSQL($quotedTableName, $quotedIdentifierColumnName)
    {
        return 'INSERT INTO ' . $quotedTableName . ' (' . $quotedIdentifierColumnName . ') VALUES (null)';
    }

    /**
     * Generates a Truncate Table SQL statement for a given table.
     *
     * Cascade is not supported on many platforms but would optionally cascade the truncate by
     * following the foreign keys.
     *
     * @param string $tableName
     * @param bool   $cascade
     *
     * @return string
     */
    public function getTruncateTableSQL($tableName, $cascade = false)
    {
        $tableIdentifier = new Identifier($tableName);

        return 'TRUNCATE ' . $tableIdentifier->getQuotedName($this);
    }

    /**
     * This is for test reasons, many vendors have special requirements for dummy statements.
     *
     * @return string
     */
    public function getDummySelectSQL()
    {
        $expression = func_num_args() > 0 ? func_get_arg(0) : '1';

        return sprintf('SELECT %s', $expression);
    }

    /**
     * Returns the SQL to create a new savepoint.
     *
     * @param string $savepoint
     *
     * @return string
     */
    public function createSavePoint($savepoint)
    {
        return 'SAVEPOINT ' . $savepoint;
    }

    /**
     * Returns the SQL to release a savepoint.
     *
     * @param string $savepoint
     *
     * @return string
     */
    public function releaseSavePoint($savepoint)
    {
        return 'RELEASE SAVEPOINT ' . $savepoint;
    }

    /**
     * Returns the SQL to rollback a savepoint.
     *
     * @param string $savepoint
     *
     * @return string
     */
    public function rollbackSavePoint($savepoint)
    {
        return 'ROLLBACK TO SAVEPOINT ' . $savepoint;
    }

    /**
     * Returns the keyword list instance of this platform.
     *
     * @throws Exception If no keyword list is specified.
     */
    final public function getReservedKeywordsList(): KeywordList
    {
        // Store the instance so it doesn't need to be generated on every request.
        return $this->_keywords ??= $this->createReservedKeywordsList();
    }

    /**
     * Creates an instance of the reserved keyword list of this platform.
     *
     * This method will become @abstract in DBAL 4.0.0.
     *
     * @throws Exception
     */
    protected function createReservedKeywordsList(): KeywordList
    {
        $class    = $this->getReservedKeywordsClass();
        $keywords = new $class();
        if (! $keywords instanceof KeywordList) {
            throw Exception::notSupported(__METHOD__);
        }

        return $keywords;
    }

    /**
     * Returns the class name of the reserved keywords list.
     *
     * @deprecated Implement {@see createReservedKeywordsList()} instead.
     *
     * @return string
     * @phpstan-return class-string<KeywordList>
     *
     * @throws Exception If not supported on this platform.
     */
    protected function getReservedKeywordsClass()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4510',
            'AbstractPlatform::getReservedKeywordsClass() is deprecated,'
                . ' use AbstractPlatform::createReservedKeywordsList() instead.',
        );

        throw Exception::notSupported(__METHOD__);
    }

    /**
     * Quotes a literal string.
     * This method is NOT meant to fix SQL injections!
     * It is only meant to escape this platform's string literal
     * quote character inside the given literal string.
     *
     * @param string $str The literal string to be quoted.
     *
     * @return string The quoted literal string.
     */
    public function quoteStringLiteral($str)
    {
        $c = $this->getStringLiteralQuoteCharacter();

        return $c . str_replace($c, $c . $c, $str) . $c;
    }

    /**
     * Gets the character used for string literal quoting.
     *
     * @deprecated Use {@see quoteStringLiteral()} to quote string literals instead.
     *
     * @return string
     */
    public function getStringLiteralQuoteCharacter()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5388',
            'AbstractPlatform::getStringLiteralQuoteCharacter() is deprecated.'
                . ' Use quoteStringLiteral() instead.',
        );

        return "'";
    }

    /**
     * Escapes metacharacters in a string intended to be used with a LIKE
     * operator.
     *
     * @param string $inputString a literal, unquoted string
     * @param string $escapeChar  should be reused by the caller in the LIKE
     *                            expression.
     */
    final public function escapeStringForLike(string $inputString, string $escapeChar): string
    {
        return preg_replace(
            '~([' . preg_quote($this->getLikeWildcardCharacters() . $escapeChar, '~') . '])~u',
            addcslashes($escapeChar, '\\') . '$1',
            $inputString,
        );
    }

    /**
     * @return array<string,mixed> An associative array with the name of the properties
     *                             of the column being declared as array indexes.
     */
    private function columnToArray(Column $column): array
    {
        $name = $column->getQuotedName($this);

        return array_merge($column->toArray(), [
            'name' => $name,
            'version' => $column->hasPlatformOption('version') ? $column->getPlatformOption('version') : false,
            'comment' => $this->getColumnComment($column),
        ]);
    }

    /** @internal */
    public function createSQLParser(): Parser
    {
        return new Parser(false);
    }

    protected function getLikeWildcardCharacters(): string
    {
        return '%_';
    }

    /**
     * Compares the definitions of the given columns in the context of this platform.
     *
     * @throws Exception
     */
    public function columnsEqual(Column $column1, Column $column2): bool
    {
        $column1Array = $this->columnToArray($column1);
        $column2Array = $this->columnToArray($column2);

        // ignore explicit columnDefinition since it's not set on the Column generated by the SchemaManager
        unset($column1Array['columnDefinition']);
        unset($column2Array['columnDefinition']);

        if (
            $this->getColumnDeclarationSQL('', $column1Array)
            !== $this->getColumnDeclarationSQL('', $column2Array)
        ) {
            return false;
        }

        if (! $this->columnDeclarationsMatch($column1, $column2)) {
            return false;
        }

        // If the platform supports inline comments, all comparison is already done above
        if ($this->supportsInlineColumnComments()) {
            return true;
        }

        if ($column1->getComment() !== $column2->getComment()) {
            return false;
        }

        // If disableTypeComments is true, we do not need to check types, all comparison is already done above
        if ($this->disableTypeComments) {
            return true;
        }

        return $column1->getType() === $column2->getType();
    }

    /**
     * Whether the database data type matches that expected for the doctrine type for the given colunms.
     */
    private function columnDeclarationsMatch(Column $column1, Column $column2): bool
    {
        return ! (
            $column1->hasPlatformOption('declarationMismatch') ||
            $column2->hasPlatformOption('declarationMismatch')
        );
    }

    /**
     * Creates the schema manager that can be used to inspect and change the underlying
     * database schema according to the dialect of the platform.
     *
     * @throws Exception
     *
     * @abstract
     */
    public function createSchemaManager(Connection $connection): AbstractSchemaManager
    {
        throw Exception::notSupported(__METHOD__);
    }
}
