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

use function gettype;
use function strtolower;

abstract class Type
{
    public static function fromValue(mixed $value, bool $allowsNull): self
    {
        if ($allowsNull === false) {
            if ($value === true) {
                return new TrueType;
            }

            if ($value === false) {
                return new FalseType;
            }
        }

        $typeName = gettype($value);

        if ($typeName === 'object') {
            return new ObjectType(TypeName::fromQualifiedName($value::class), $allowsNull);
        }

        $type = self::fromName($typeName, $allowsNull);

        if ($type instanceof SimpleType) {
            $type = new SimpleType($typeName, $allowsNull, $value);
        }

        return $type;
    }

    public static function fromName(string $typeName, bool $allowsNull): self
    {
        return match (strtolower($typeName)) {
            'callable'     => new CallableType($allowsNull),
            'true'         => new TrueType,
            'false'        => new FalseType,
            'iterable'     => new IterableType($allowsNull),
            'never'        => new NeverType,
            'null'         => new NullType,
            'object'       => new GenericObjectType($allowsNull),
            'unknown type' => new UnknownType,
            'void'         => new VoidType,
            'array', 'bool', 'boolean', 'double', 'float', 'int', 'integer', 'real', 'resource', 'resource (closed)', 'string' => new SimpleType($typeName, $allowsNull),
            'mixed' => new MixedType,
            default => new ObjectType(TypeName::fromQualifiedName($typeName), $allowsNull),
        };
    }

    public function asString(): string
    {
        return ($this->allowsNull() ? '?' : '') . $this->name();
    }

    /**
     * @psalm-assert-if-true CallableType $this
     */
    public function isCallable(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true TrueType $this
     */
    public function isTrue(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true FalseType $this
     */
    public function isFalse(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true GenericObjectType $this
     */
    public function isGenericObject(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true IntersectionType $this
     */
    public function isIntersection(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true IterableType $this
     */
    public function isIterable(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true MixedType $this
     */
    public function isMixed(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true NeverType $this
     */
    public function isNever(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true NullType $this
     */
    public function isNull(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true ObjectType $this
     */
    public function isObject(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true SimpleType $this
     */
    public function isSimple(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true StaticType $this
     */
    public function isStatic(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true UnionType $this
     */
    public function isUnion(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true UnknownType $this
     */
    public function isUnknown(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true VoidType $this
     */
    public function isVoid(): bool
    {
        return false;
    }

    abstract public function isAssignable(self $other): bool;

    abstract public function name(): string;

    abstract public function allowsNull(): bool;
}
