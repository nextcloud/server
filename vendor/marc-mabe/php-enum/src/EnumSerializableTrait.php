<?php

declare(strict_types=1);

namespace MabeEnum;

use RuntimeException;
use LogicException;

/**
 * Trait to make enumerations serializable
 *
 * This trait is a workaround to make enumerations serializable.
 *
 * Please note that this feature breaks singleton behaviour of your enumerations
 * if an enumeration will be unserialized after it was instantiated already.
 *
 * @copyright 2020, Marc Bennewitz
 * @license http://github.com/marc-mabe/php-enum/blob/master/LICENSE.txt New BSD License
 * @link http://github.com/marc-mabe/php-enum for the canonical source repository
 * @link https://github.com/marc-mabe/php-enum/issues/52 for further information about this feature
 */
trait EnumSerializableTrait
{
    /**
     * The method will be defined by MabeEnum\Enum
     * @return null|bool|int|float|string|array<mixed>
     */
    abstract public function getValue();

    /**
     * Returns an array of data to be serialized.
     * This magic method will be called by serialize() in PHP >= 7.4
     *
     * @return array<string, null|bool|int|float|string|array<mixed>>
     */
    public function __serialize(): array
    {
        return ['value' => $this->getValue()];
    }

    /**
     * Receives an array of data to be unserialized on a new instance without constructor.
     * This magic method will be called in PHP >= 7.4 is the data where serialized with PHP >= 7.4.
     *
     * @throws RuntimeException On missing, unknown or invalid value
     * @throws LogicException   On calling this method on an already initialized enumerator
     *
     * @param array<string, mixed> $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        if (!\array_key_exists('value', $data)) {
            throw new RuntimeException('Missing array key "value"');
        }

        /** @var mixed $value */
        $value     = $data['value'];
        $constants = self::getConstants();
        $name      = \array_search($value, $constants, true);
        if ($name === false) {
            $message = \is_scalar($value)
                ? 'Unknown value ' . \var_export($value, true)
                : 'Invalid value of type ' . (\is_object($value) ? \get_class($value) : \gettype($value));
            throw new RuntimeException($message);
        }

        $class      = static::class;
        $enumerator = $this;
        $closure    = function () use ($class, $name, $value, $enumerator) {
            if ($value !== null && $this->value !== null) {
                throw new LogicException('Do not call this directly - please use unserialize($enum) instead');
            }

            $this->value = $value;

            if (!isset(self::$instances[$class][$name])) {
                self::$instances[$class][$name] = $enumerator;
            }
        };
        $closure->bindTo($this, Enum::class)();
    }

    /**
     * Serialize the value of the enumeration
     * This will be called automatically on `serialize()` if the enumeration implements the `Serializable` interface
     *
     * @return string
     * @deprecated Since PHP 7.4
     */
    public function serialize(): string
    {
        return \serialize($this->getValue());
    }

    /**
     * Unserializes a given serialized value and push it into the current instance
     * This will be called automatically on `unserialize()` if the enumeration implements the `Serializable` interface
     * @param string $serialized
     * @return void
     * @throws RuntimeException On an unknown or invalid value
     * @throws LogicException   On calling this method on an already initialized enumerator
     * @deprecated Since PHP 7.4
     */
    public function unserialize($serialized): void
    {
        $this->__unserialize(['value' => \unserialize($serialized)]);
    }
}
