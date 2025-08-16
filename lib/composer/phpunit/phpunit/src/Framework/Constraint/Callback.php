<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Framework\Constraint;

use Closure;
use ReflectionFunction;

/**
 * @psalm-template CallbackInput of mixed
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
final class Callback extends Constraint
{
    /**
     * @psalm-var callable(CallbackInput $input): bool
     */
    private readonly mixed $callback;

    /**
     * @psalm-param callable(CallbackInput $input): bool $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Returns a string representation of the constraint.
     */
    public function toString(): string
    {
        return 'is accepted by specified callback';
    }

    /**
     * @psalm-suppress ArgumentTypeCoercion
     */
    public function isVariadic(): bool
    {
        foreach ((new ReflectionFunction(Closure::fromCallable($this->callback)))->getParameters() as $parameter) {
            if ($parameter->isVariadic()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluates the constraint for parameter $value. Returns true if the
     * constraint is met, false otherwise.
     *
     * @psalm-param CallbackInput $other
     *
     * @psalm-suppress InvalidArgument
     */
    protected function matches(mixed $other): bool
    {
        if ($this->isVariadic()) {
            return ($this->callback)(...$other);
        }

        return ($this->callback)($other);
    }
}
