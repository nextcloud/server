<?php

declare(strict_types=1);

namespace MabeEnum;

use ReflectionClass;
use InvalidArgumentException;
use LogicException;

/**
 * Abstract base enumeration class.
 *
 * @copyright 2020, Marc Bennewitz
 * @license http://github.com/marc-mabe/php-enum/blob/master/LICENSE.txt New BSD License
 * @link http://github.com/marc-mabe/php-enum for the canonical source repository
 *
 * @psalm-immutable
 */
abstract class Enum implements \Stringable
{
    /**
     * The selected enumerator value
     *
     * @var null|bool|int|float|string|array<mixed>
     */
    private $value;

    /**
     * The ordinal number of the enumerator
     *
     * @var null|int
     */
    private $ordinal;

    /**
     * A map of enumerator names and values by enumeration class
     *
     * @var array<class-string<Enum>, array<string, null|bool|int|float|string|array<mixed>>>
     */
    private static $constants = [];

    /**
     * A List of available enumerator names by enumeration class
     *
     * @var array<class-string<Enum>, string[]>
     */
    private static $names = [];

    /**
     * A map of enumerator names and instances by enumeration class
     *
     * @var array<class-string<Enum>, array<string, Enum>>
     */
    private static $instances = [];

    /**
     * Constructor
     *
     * @param null|bool|int|float|string|array<mixed> $value   The value of the enumerator
     * @param int|null                                $ordinal The ordinal number of the enumerator
     */
    final private function __construct($value, $ordinal = null)
    {
        $this->value   = $value;
        $this->ordinal = $ordinal;
    }

    /**
     * Get the name of the enumerator
     *
     * @return string
     * @see getName()
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @throws LogicException Enums are not cloneable
     *                        because instances are implemented as singletons
     */
    final public function __clone()
    {
        throw new LogicException('Enums are not cloneable');
    }

    /**
     * @throws LogicException Serialization is not supported by default in this pseudo-enum implementation
     *
     * @psalm-return never-return
     */
    final public function __sleep()
    {
        throw new LogicException('Serialization is not supported by default in this pseudo-enum implementation');
    }

    /**
     * @throws LogicException Serialization is not supported by default in this pseudo-enum implementation
     *
     * @psalm-return never-return
     */
    final public function __wakeup()
    {
        throw new LogicException('Serialization is not supported by default in this pseudo-enum implementation');
    }

    /**
     * Get the value of the enumerator
     *
     * @return null|bool|int|float|string|array<mixed>
     */
    final public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the name of the enumerator
     *
     * @return string
     *
     * @phpstan-return string
     * @psalm-return non-empty-string
     */
    final public function getName()
    {
        return self::$names[static::class][$this->ordinal ?? $this->getOrdinal()];
    }

    /**
     * Get the ordinal number of the enumerator
     *
     * @return int
     */
    final public function getOrdinal()
    {
        if ($this->ordinal === null) {
            $ordinal   = 0;
            $value     = $this->value;
            $constants = self::$constants[static::class] ?? static::getConstants();
            foreach ($constants as $constValue) {
                if ($value === $constValue) {
                    break;
                }
                ++$ordinal;
            }

            $this->ordinal = $ordinal;
        }

        return $this->ordinal;
    }

    /**
     * Compare this enumerator against another and check if it's the same.
     *
     * @param static|null|bool|int|float|string|array<mixed> $enumerator An enumerator object or value
     * @return bool
     */
    final public function is($enumerator)
    {
        return $this === $enumerator || $this->value === $enumerator

            // The following additional conditions are required only because of the issue of serializable singletons
            || ($enumerator instanceof static
                && \get_class($enumerator) === static::class
                && $enumerator->value === $this->value
            );
    }

    /**
     * Get an enumerator instance of the given enumerator value or instance
     *
     * @param static|null|bool|int|float|string|array<mixed> $enumerator An enumerator object or value
     * @return static
     * @throws InvalidArgumentException On an unknown or invalid value
     * @throws LogicException           On ambiguous constant values
     *
     * @psalm-pure
     */
    final public static function get($enumerator)
    {
        if ($enumerator instanceof static) {
            if (\get_class($enumerator) !== static::class) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid value of type %s for enumeration %s',
                    \get_class($enumerator),
                    static::class
                ));
            }

            return $enumerator;
        }

        return static::byValue($enumerator);
    }

    /**
     * Get an enumerator instance by the given value
     *
     * @param null|bool|int|float|string|array<mixed> $value Enumerator value
     * @return static
     * @throws InvalidArgumentException On an unknown or invalid value
     * @throws LogicException           On ambiguous constant values
     *
     * @psalm-pure
     */
    final public static function byValue($value)
    {
        /** @var mixed $value */

        $constants = self::$constants[static::class] ?? static::getConstants();

        $name = \array_search($value, $constants, true);
        if ($name === false) {
            throw new InvalidArgumentException(sprintf(
                'Unknown value %s for enumeration %s',
                \is_scalar($value)
                    ? \var_export($value, true)
                    : 'of type ' . (\is_object($value) ? \get_class($value) : \gettype($value)),
                static::class
            ));
        }

        /** @var static $instance */
        $instance = self::$instances[static::class][$name]
            ?? self::$instances[static::class][$name] = new static($constants[$name]);

        return $instance;
    }

    /**
     * Get an enumerator instance by the given name
     *
     * @param string $name The name of the enumerator
     * @return static
     * @throws InvalidArgumentException On an invalid or unknown name
     * @throws LogicException           On ambiguous values
     *
     * @psalm-pure
     */
    final public static function byName(string $name)
    {
        if (isset(self::$instances[static::class][$name])) {
            /** @var static $instance */
            $instance = self::$instances[static::class][$name];
            return $instance;
        }

        $const = static::class . "::{$name}";
        if (!\defined($const)) {
            throw new InvalidArgumentException("{$const} not defined");
        }

        assert(
            self::noAmbiguousValues(static::getConstants()),
            'Ambiguous enumerator values detected for ' . static::class
        );

        /** @var array<int|string, mixed>|bool|float|int|string|null $value */
        $value = \constant($const);
        return self::$instances[static::class][$name] = new static($value);
    }

    /**
     * Get an enumeration instance by the given ordinal number
     *
     * @param int $ordinal The ordinal number of the enumerator
     * @return static
     * @throws InvalidArgumentException On an invalid ordinal number
     * @throws LogicException           On ambiguous values
     *
     * @psalm-pure
     */
    final public static function byOrdinal(int $ordinal)
    {
        $constants = self::$constants[static::class] ?? static::getConstants();

        if (!isset(self::$names[static::class][$ordinal])) {
            throw new InvalidArgumentException(\sprintf(
                'Invalid ordinal number %s, must between 0 and %s',
                $ordinal,
                \count(self::$names[static::class]) - 1
            ));
        }

        $name = self::$names[static::class][$ordinal];

        /** @var static $instance */
        $instance = self::$instances[static::class][$name]
            ?? self::$instances[static::class][$name] = new static($constants[$name], $ordinal);

        return $instance;
    }

    /**
     * Get a list of enumerator instances ordered by ordinal number
     *
     * @return static[]
     *
     * @phpstan-return array<int, static>
     * @psalm-return list<static>
     * @psalm-pure
     */
    final public static function getEnumerators()
    {
        if (!isset(self::$names[static::class])) {
            static::getConstants();
        }

        /** @var callable $byNameFn */
        $byNameFn = [static::class, 'byName'];
        return \array_map($byNameFn, self::$names[static::class]);
    }

    /**
     * Get a list of enumerator values ordered by ordinal number
     *
     * @return (null|bool|int|float|string|array)[]
     *
     * @phpstan-return array<int, null|bool|int|float|string|array<int|string, mixed>>
     * @psalm-return list<null|bool|int|float|string|array>
     * @psalm-pure
     */
    final public static function getValues()
    {
        return \array_values(self::$constants[static::class] ?? static::getConstants());
    }

    /**
     * Get a list of enumerator names ordered by ordinal number
     *
     * @return string[]
     *
     * @phpstan-return array<int, string>
     * @psalm-return list<non-empty-string>
     * @psalm-pure
     */
    final public static function getNames()
    {
        if (!isset(self::$names[static::class])) {
            static::getConstants();
        }
        return self::$names[static::class];
    }

    /**
     * Get a list of enumerator ordinal numbers
     *
     * @return int[]
     *
     * @phpstan-return array<int, int>
     * @psalm-return list<int>
     * @psalm-pure
     */
    final public static function getOrdinals()
    {
        $count = \count(self::$constants[static::class] ?? static::getConstants());
        return $count ? \range(0, $count - 1) : [];
    }

    /**
     * Get all available constants of the called class
     *
     * @return (null|bool|int|float|string|array)[]
     * @throws LogicException On ambiguous constant values
     *
     * @phpstan-return array<string, null|bool|int|float|string|array<int|string, mixed>>
     * @psalm-return array<non-empty-string, null|bool|int|float|string|array>
     * @psalm-pure
     */
    final public static function getConstants()
    {
        if (isset(self::$constants[static::class])) {
            return self::$constants[static::class];
        }

        $reflection = new ReflectionClass(static::class);
        $constants  = [];

        do {
            $scopeConstants = [];
            // Enumerators must be defined as public class constants
            foreach ($reflection->getReflectionConstants() as $reflConstant) {
                if ($reflConstant->isPublic()) {
                    $scopeConstants[ $reflConstant->getName() ] = $reflConstant->getValue();
                }
            }

            $constants = $scopeConstants + $constants;
        } while (($reflection = $reflection->getParentClass()) && $reflection->name !== __CLASS__);

        /** @var array<string, null|bool|int|float|string|array<mixed>> $constants */

        assert(
            self::noAmbiguousValues($constants),
            'Ambiguous enumerator values detected for ' . static::class
        );

        self::$names[static::class] = \array_keys($constants);
        return self::$constants[static::class] = $constants;
    }

    /**
     * Test that the given constants does not contain ambiguous values
     * @param array<string, null|bool|int|float|string|array<mixed>> $constants
     * @return bool
     */
    private static function noAmbiguousValues($constants)
    {
        foreach ($constants as $value) {
            $names = \array_keys($constants, $value, true);
            if (\count($names) > 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Test if the given enumerator is part of this enumeration
     *
     * @param static|null|bool|int|float|string|array<mixed> $enumerator
     * @return bool
     *
     * @psalm-pure
     */
    final public static function has($enumerator)
    {
        if ($enumerator instanceof static) {
            return \get_class($enumerator) === static::class;
        }

        return static::hasValue($enumerator);
    }

    /**
     * Test if the given enumerator value is part of this enumeration
     *
     * @param null|bool|int|float|string|array<mixed> $value
     * @return bool
     *
     * @psalm-pure
     */
    final public static function hasValue($value)
    {
        return \in_array($value, self::$constants[static::class] ?? static::getConstants(), true);
    }

    /**
     * Test if the given enumerator name is part of this enumeration
     *
     * @param string $name
     * @return bool
     *
     * @psalm-pure
     */
    final public static function hasName(string $name)
    {
        return \defined("static::{$name}");
    }

    /**
     * Get an enumerator instance by the given name.
     *
     * This will be called automatically on calling a method
     * with the same name of a defined enumerator.
     *
     * @param string       $method The name of the enumerator (called as method)
     * @param array<mixed> $args   There should be no arguments
     * @return static
     * @throws InvalidArgumentException On an invalid or unknown name
     * @throws LogicException           On ambiguous constant values
     *
     * @psalm-pure
     */
    final public static function __callStatic(string $method, array $args)
    {
        return static::byName($method);
    }
}
