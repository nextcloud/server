<?php

namespace Doctrine\DBAL\Types;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Deprecations\Deprecation;

use function array_map;
use function get_class;

/**
 * The base class for so-called Doctrine mapping types.
 *
 * A Type object is obtained by calling the static {@see getType()} method.
 */
abstract class Type
{
    /**
     * The map of supported doctrine mapping types.
     */
    private const BUILTIN_TYPES_MAP = [
        Types::ARRAY                => ArrayType::class,
        Types::ASCII_STRING         => AsciiStringType::class,
        Types::BIGINT               => BigIntType::class,
        Types::BINARY               => BinaryType::class,
        Types::BLOB                 => BlobType::class,
        Types::BOOLEAN              => BooleanType::class,
        Types::DATE_MUTABLE         => DateType::class,
        Types::DATE_IMMUTABLE       => DateImmutableType::class,
        Types::DATEINTERVAL         => DateIntervalType::class,
        Types::DATETIME_MUTABLE     => DateTimeType::class,
        Types::DATETIME_IMMUTABLE   => DateTimeImmutableType::class,
        Types::DATETIMETZ_MUTABLE   => DateTimeTzType::class,
        Types::DATETIMETZ_IMMUTABLE => DateTimeTzImmutableType::class,
        Types::DECIMAL              => DecimalType::class,
        Types::FLOAT                => FloatType::class,
        Types::GUID                 => GuidType::class,
        Types::INTEGER              => IntegerType::class,
        Types::JSON                 => JsonType::class,
        Types::OBJECT               => ObjectType::class,
        Types::SIMPLE_ARRAY         => SimpleArrayType::class,
        Types::SMALLINT             => SmallIntType::class,
        Types::STRING               => StringType::class,
        Types::TEXT                 => TextType::class,
        Types::TIME_MUTABLE         => TimeType::class,
        Types::TIME_IMMUTABLE       => TimeImmutableType::class,
    ];

    private static ?TypeRegistry $typeRegistry = null;

    /** @internal Do not instantiate directly - use {@see Type::addType()} method instead. */
    final public function __construct()
    {
    }

    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param mixed            $value    The value to convert.
     * @param AbstractPlatform $platform The currently used database platform.
     *
     * @return mixed The database representation of the value.
     *
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    /**
     * Converts a value from its database representation to its PHP representation
     * of this type.
     *
     * @param mixed            $value    The value to convert.
     * @param AbstractPlatform $platform The currently used database platform.
     *
     * @return mixed The PHP representation of the value.
     *
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    /**
     * Gets the SQL declaration snippet for a column of this type.
     *
     * @param mixed[]          $column   The column definition
     * @param AbstractPlatform $platform The currently used database platform.
     *
     * @return string
     */
    abstract public function getSQLDeclaration(array $column, AbstractPlatform $platform);

    /**
     * Gets the name of this type.
     *
     * @deprecated this method will be removed in Doctrine DBAL 4.0,
     *             use {@see TypeRegistry::lookupName()} instead.
     *
     * @return string
     */
    abstract public function getName();

    final public static function getTypeRegistry(): TypeRegistry
    {
        return self::$typeRegistry ??= self::createTypeRegistry();
    }

    private static function createTypeRegistry(): TypeRegistry
    {
        $instances = [];

        foreach (self::BUILTIN_TYPES_MAP as $name => $class) {
            $instances[$name] = new $class();
        }

        return new TypeRegistry($instances);
    }

    /**
     * Factory method to create type instances.
     * Type instances are implemented as flyweights.
     *
     * @param string $name The name of the type (as returned by getName()).
     *
     * @return Type
     *
     * @throws Exception
     */
    public static function getType($name)
    {
        return self::getTypeRegistry()->get($name);
    }

    /**
     * Finds a name for the given type.
     *
     * @throws Exception
     */
    public static function lookupName(self $type): string
    {
        return self::getTypeRegistry()->lookupName($type);
    }

    /**
     * Adds a custom type to the type map.
     *
     * @param string             $name      The name of the type. This should correspond to what getName() returns.
     * @param class-string<Type> $className The class name of the custom type.
     *
     * @return void
     *
     * @throws Exception
     */
    public static function addType($name, $className)
    {
        self::getTypeRegistry()->register($name, new $className());
    }

    /**
     * Checks if exists support for a type.
     *
     * @param string $name The name of the type.
     *
     * @return bool TRUE if type is supported; FALSE otherwise.
     */
    public static function hasType($name)
    {
        return self::getTypeRegistry()->has($name);
    }

    /**
     * Overrides an already defined type to use a different implementation.
     *
     * @param string             $name
     * @param class-string<Type> $className
     *
     * @return void
     *
     * @throws Exception
     */
    public static function overrideType($name, $className)
    {
        self::getTypeRegistry()->override($name, new $className());
    }

    /**
     * Gets the (preferred) binding type for values of this type that
     * can be used when binding parameters to prepared statements.
     *
     * This method should return one of the {@see ParameterType} constants.
     *
     * @return int
     */
    public function getBindingType()
    {
        return ParameterType::STRING;
    }

    /**
     * Gets the types array map which holds all registered types and the corresponding
     * type class
     *
     * @return array<string, string>
     */
    public static function getTypesMap()
    {
        return array_map(
            static function (Type $type): string {
                return get_class($type);
            },
            self::getTypeRegistry()->getMap(),
        );
    }

    /**
     * Does working with this column require SQL conversion functions?
     *
     * This is a metadata function that is required for example in the ORM.
     * Usage of {@see convertToDatabaseValueSQL} and
     * {@see convertToPHPValueSQL} works for any type and mostly
     * does nothing. This method can additionally be used for optimization purposes.
     *
     * @deprecated Consumers should call {@see convertToDatabaseValueSQL} and {@see convertToPHPValueSQL}
     * regardless of the type.
     *
     * @return bool
     */
    public function canRequireSQLConversion()
    {
        return false;
    }

    /**
     * Modifies the SQL expression (identifier, parameter) to convert to a database value.
     *
     * @param string $sqlExpr
     *
     * @return string
     */
    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        return $sqlExpr;
    }

    /**
     * Modifies the SQL expression (identifier, parameter) to convert to a PHP value.
     *
     * @param string           $sqlExpr
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function convertToPHPValueSQL($sqlExpr, $platform)
    {
        return $sqlExpr;
    }

    /**
     * Gets an array of database types that map to this Doctrine type.
     *
     * @return string[]
     */
    public function getMappedDatabaseTypes(AbstractPlatform $platform)
    {
        return [];
    }

    /**
     * If this Doctrine Type maps to an already mapped database type,
     * reverse schema engineering can't tell them apart. You need to mark
     * one of those types as commented, which will have Doctrine use an SQL
     * comment to typehint the actual Doctrine Type.
     *
     * @deprecated
     *
     * @return bool
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5509',
            '%s is deprecated.',
            __METHOD__,
        );

        return false;
    }
}
