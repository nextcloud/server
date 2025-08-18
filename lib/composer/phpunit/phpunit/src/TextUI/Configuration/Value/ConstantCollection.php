<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\TextUI\Configuration;

use function count;
use Countable;
use IteratorAggregate;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @psalm-immutable
 *
 * @template-implements IteratorAggregate<int, Constant>
 */
final class ConstantCollection implements Countable, IteratorAggregate
{
    /**
     * @psalm-var list<Constant>
     */
    private readonly array $constants;

    /**
     * @psalm-param list<Constant> $constants
     */
    public static function fromArray(array $constants): self
    {
        return new self(...$constants);
    }

    private function __construct(Constant ...$constants)
    {
        $this->constants = $constants;
    }

    /**
     * @psalm-return list<Constant>
     */
    public function asArray(): array
    {
        return $this->constants;
    }

    public function count(): int
    {
        return count($this->constants);
    }

    public function getIterator(): ConstantCollectionIterator
    {
        return new ConstantCollectionIterator($this);
    }
}
