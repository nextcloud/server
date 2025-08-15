<?php declare(strict_types=1);
/*
 * This file is part of sebastian/type.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Type;

use function assert;
use function class_exists;
use function is_iterable;
use ReflectionClass;

final class IterableType extends Type
{
    private bool $allowsNull;

    public function __construct(bool $nullable)
    {
        $this->allowsNull = $nullable;
    }

    /**
     * @throws RuntimeException
     */
    public function isAssignable(Type $other): bool
    {
        if ($this->allowsNull && $other instanceof NullType) {
            return true;
        }

        if ($other instanceof self) {
            return true;
        }

        if ($other instanceof SimpleType) {
            return is_iterable($other->value());
        }

        if ($other instanceof ObjectType) {
            $className = $other->className()->qualifiedName();

            assert(class_exists($className));

            return (new ReflectionClass($className))->isIterable();
        }

        return false;
    }

    public function name(): string
    {
        return 'iterable';
    }

    public function allowsNull(): bool
    {
        return $this->allowsNull;
    }

    /**
     * @psalm-assert-if-true IterableType $this
     */
    public function isIterable(): bool
    {
        return true;
    }
}
