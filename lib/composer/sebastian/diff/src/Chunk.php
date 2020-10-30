<?php declare(strict_types=1);
/*
 * This file is part of sebastian/diff.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Diff;

final class Chunk
{
    /**
     * @var int
     */
    private $start;

    /**
     * @var int
     */
    private $startRange;

    /**
     * @var int
     */
    private $end;

    /**
     * @var int
     */
    private $endRange;

    /**
     * @var Line[]
     */
    private $lines;

    public function __construct(int $start = 0, int $startRange = 1, int $end = 0, int $endRange = 1, array $lines = [])
    {
        $this->start      = $start;
        $this->startRange = $startRange;
        $this->end        = $end;
        $this->endRange   = $endRange;
        $this->lines      = $lines;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getStartRange(): int
    {
        return $this->startRange;
    }

    public function getEnd(): int
    {
        return $this->end;
    }

    public function getEndRange(): int
    {
        return $this->endRange;
    }

    /**
     * @return Line[]
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    /**
     * @param Line[] $lines
     */
    public function setLines(array $lines): void
    {
        foreach ($lines as $line) {
            if (!$line instanceof Line) {
                throw new InvalidArgumentException;
            }
        }

        $this->lines = $lines;
    }
}
