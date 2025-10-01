<?php

namespace Doctrine\DBAL;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use SensitiveParameter;

use function get_class;
use function gettype;
use function implode;
use function is_object;
use function spl_object_hash;
use function sprintf;

class Exception extends \Exception
{
    public static function notSupported(string $method): self
    {
        return new self(sprintf("Operation '%s' is not supported by platform.", $method));
    }

    /** @param mixed $invalidPlatform */
    public static function invalidPlatformType($invalidPlatform): self
    {
        if (is_object($invalidPlatform)) {
            return new self(
                sprintf(
                    "Option 'platform' must be a subtype of '%s', instance of '%s' given",
                    AbstractPlatform::class,
                    get_class($invalidPlatform),
                ),
            );
        }

        return new self(
            sprintf(
                "Option 'platform' must be an object and subtype of '%s'. Got '%s'",
                AbstractPlatform::class,
                gettype($invalidPlatform),
            ),
        );
    }

    /**
     * Returns a new instance for an invalid specified platform version.
     *
     * @param string $version        The invalid platform version given.
     * @param string $expectedFormat The expected platform version format.
     */
    public static function invalidPlatformVersionSpecified(string $version, string $expectedFormat): self
    {
        return new self(
            sprintf(
                'Invalid platform version "%s" specified. ' .
                'The platform version has to be specified in the format: "%s".',
                $version,
                $expectedFormat,
            ),
        );
    }

    /** @param string|null $url The URL that was provided in the connection parameters (if any). */
    public static function driverRequired(
        #[SensitiveParameter]
        ?string $url = null
    ): self {
        if ($url !== null) {
            return new self(
                sprintf(
                    "The options 'driver' or 'driverClass' are mandatory if a connection URL without scheme " .
                    'is given to DriverManager::getConnection(). Given URL: %s',
                    $url,
                ),
            );
        }

        return new self("The options 'driver' or 'driverClass' are mandatory if no PDO " .
            'instance is given to DriverManager::getConnection().');
    }

    /** @param string[] $knownDrivers */
    public static function unknownDriver(string $unknownDriverName, array $knownDrivers): self
    {
        return new self("The given 'driver' " . $unknownDriverName . ' is unknown, ' .
            'Doctrine currently supports only the following drivers: ' . implode(', ', $knownDrivers));
    }

    public static function invalidWrapperClass(string $wrapperClass): self
    {
        return new self("The given 'wrapperClass' " . $wrapperClass . ' has to be a ' .
            'subtype of \Doctrine\DBAL\Connection.');
    }

    public static function invalidDriverClass(string $driverClass): self
    {
        return new self(
            "The given 'driverClass' " . $driverClass . ' has to implement the ' . Driver::class . ' interface.',
        );
    }

    public static function noColumnsSpecifiedForTable(string $tableName): self
    {
        return new self('No columns specified for table ' . $tableName);
    }

    public static function typeExists(string $name): self
    {
        return new self('Type ' . $name . ' already exists.');
    }

    public static function unknownColumnType(string $name): self
    {
        return new self('Unknown column type "' . $name . '" requested. Any Doctrine type that you use has ' .
            'to be registered with \Doctrine\DBAL\Types\Type::addType(). You can get a list of all the ' .
            'known types with \Doctrine\DBAL\Types\Type::getTypesMap(). If this error occurs during database ' .
            'introspection then you might have forgotten to register all database types for a Doctrine Type. Use ' .
            'AbstractPlatform#registerDoctrineTypeMapping() or have your custom types implement ' .
            'Type#getMappedDatabaseTypes(). If the type name is empty you might ' .
            'have a problem with the cache or forgot some mapping information.');
    }

    public static function typeNotFound(string $name): self
    {
        return new self('Type to be overwritten ' . $name . ' does not exist.');
    }

    public static function typeNotRegistered(Type $type): self
    {
        return new self(
            sprintf('Type of the class %s@%s is not registered.', get_class($type), spl_object_hash($type)),
        );
    }

    public static function typeAlreadyRegistered(Type $type): self
    {
        return new self(
            sprintf('Type of the class %s@%s is already registered.', get_class($type), spl_object_hash($type)),
        );
    }
}
