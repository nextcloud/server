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
 * @template-implements IteratorAggregate<int, File>
 */
final class FileCollection implements Countable, IteratorAggregate
{
    /**
     * @psalm-var list<File>
     */
    private readonly array $files;

    /**
     * @psalm-param list<File> $files
     */
    public static function fromArray(array $files): self
    {
        return new self(...$files);
    }

    private function __construct(File ...$files)
    {
        $this->files = $files;
    }

    /**
     * @psalm-return list<File>
     */
    public function asArray(): array
    {
        return $this->files;
    }

    public function count(): int
    {
        return count($this->files);
    }

    public function notEmpty(): bool
    {
        return !empty($this->files);
    }

    public function getIterator(): FileCollectionIterator
    {
        return new FileCollectionIterator($this);
    }
}
