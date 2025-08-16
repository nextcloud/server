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

use IteratorAggregate;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @psalm-immutable
 *
 * @template-implements IteratorAggregate<int, Group>
 */
final class GroupCollection implements IteratorAggregate
{
    /**
     * @psalm-var list<Group>
     */
    private readonly array $groups;

    /**
     * @psalm-param list<Group> $groups
     */
    public static function fromArray(array $groups): self
    {
        return new self(...$groups);
    }

    private function __construct(Group ...$groups)
    {
        $this->groups = $groups;
    }

    /**
     * @psalm-return list<Group>
     */
    public function asArray(): array
    {
        return $this->groups;
    }

    /**
     * @psalm-return list<string>
     */
    public function asArrayOfStrings(): array
    {
        $result = [];

        foreach ($this->groups as $group) {
            $result[] = $group->name();
        }

        return $result;
    }

    public function isEmpty(): bool
    {
        return empty($this->groups);
    }

    public function getIterator(): GroupCollectionIterator
    {
        return new GroupCollectionIterator($this);
    }
}
