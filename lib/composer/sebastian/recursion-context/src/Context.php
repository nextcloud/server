<?php declare(strict_types=1);
/*
 * This file is part of sebastian/recursion-context.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\RecursionContext;

use const PHP_INT_MAX;
use const PHP_INT_MIN;
use function array_key_exists;
use function array_pop;
use function array_slice;
use function count;
use function is_array;
use function random_int;
use function spl_object_hash;
use SplObjectStorage;

final class Context
{
    private array $arrays = [];
    private SplObjectStorage $objects;

    public function __construct()
    {
        $this->objects = new SplObjectStorage;
    }

    /**
     * @codeCoverageIgnore
     */
    public function __destruct()
    {
        foreach ($this->arrays as &$array) {
            if (is_array($array)) {
                array_pop($array);
                array_pop($array);
            }
        }
    }

    /**
     * @psalm-template T of object|array
     *
     * @psalm-param T $value
     *
     * @param-out T $value
     */
    public function add(array|object &$value): false|int|string
    {
        if (is_array($value)) {
            return $this->addArray($value);
        }

        return $this->addObject($value);
    }

    /**
     * @psalm-template T of object|array
     *
     * @psalm-param T $value
     *
     * @param-out T $value
     */
    public function contains(array|object &$value): false|int|string
    {
        if (is_array($value)) {
            return $this->containsArray($value);
        }

        return $this->containsObject($value);
    }

    private function addArray(array &$array): int
    {
        $key = $this->containsArray($array);

        if ($key !== false) {
            return $key;
        }

        $key            = count($this->arrays);
        $this->arrays[] = &$array;

        if (!array_key_exists(PHP_INT_MAX, $array) && !array_key_exists(PHP_INT_MAX - 1, $array)) {
            $array[] = $key;
            $array[] = $this->objects;
        } else {
            /* Cover the improbable case, too.
             *
             * Note that array_slice() (used in containsArray()) will return the
             * last two values added, *not necessarily* the highest integer keys
             * in the array. Therefore, the order of these writes to $array is
             * important, but the actual keys used is not. */
            do {
                /** @noinspection PhpUnhandledExceptionInspection */
                $key = random_int(PHP_INT_MIN, PHP_INT_MAX);
            } while (array_key_exists($key, $array));

            $array[$key] = $key;

            do {
                /** @noinspection PhpUnhandledExceptionInspection */
                $key = random_int(PHP_INT_MIN, PHP_INT_MAX);
            } while (array_key_exists($key, $array));

            $array[$key] = $this->objects;
        }

        return $key;
    }

    private function addObject(object $object): string
    {
        if (!$this->objects->offsetExists($object)) {
            $this->objects->offsetSet($object);
        }

        return spl_object_hash($object);
    }

    private function containsArray(array $array): false|int
    {
        $end = array_slice($array, -2);

        return isset($end[1]) && $end[1] === $this->objects ? $end[0] : false;
    }

    private function containsObject(object $value): false|string
    {
        if ($this->objects->offsetExists($value)) {
            return spl_object_hash($value);
        }

        return false;
    }
}
