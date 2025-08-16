<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Event\Code;

use function count;
use Iterator;

/**
 * @template-implements Iterator<int, Test>
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
final class TestCollectionIterator implements Iterator
{
    /**
     * @psalm-var list<Test>
     */
    private readonly array $tests;
    private int $position = 0;

    public function __construct(TestCollection $tests)
    {
        $this->tests = $tests->asArray();
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return $this->position < count($this->tests);
    }

    public function key(): int
    {
        return $this->position;
    }

    public function current(): Test
    {
        return $this->tests[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }
}
