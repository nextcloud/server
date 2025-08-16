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

final class TrueType extends Type
{
    public function isAssignable(Type $other): bool
    {
        if ($other instanceof self) {
            return true;
        }

        return $other instanceof SimpleType &&
              $other->name() === 'bool' &&
              $other->value() === true;
    }

    public function name(): string
    {
        return 'true';
    }

    public function allowsNull(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true TrueType $this
     */
    public function isTrue(): bool
    {
        return true;
    }
}
