<?php declare(strict_types=1);
/*
 * This file is part of sebastian/lines-of-code.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\LinesOfCode;

/**
 * @psalm-immutable
 */
final class LinesOfCode
{
    /**
     * @psalm-var non-negative-int
     */
    private readonly int $linesOfCode;

    /**
     * @psalm-var non-negative-int
     */
    private readonly int $commentLinesOfCode;

    /**
     * @psalm-var non-negative-int
     */
    private readonly int $nonCommentLinesOfCode;

    /**
     * @psalm-var non-negative-int
     */
    private readonly int $logicalLinesOfCode;

    /**
     * @psalm-param non-negative-int $linesOfCode
     * @psalm-param non-negative-int $commentLinesOfCode
     * @psalm-param non-negative-int $nonCommentLinesOfCode
     * @psalm-param non-negative-int $logicalLinesOfCode
     *
     * @throws IllogicalValuesException
     * @throws NegativeValueException
     */
    public function __construct(int $linesOfCode, int $commentLinesOfCode, int $nonCommentLinesOfCode, int $logicalLinesOfCode)
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if ($linesOfCode < 0) {
            throw new NegativeValueException('$linesOfCode must not be negative');
        }

        /** @psalm-suppress DocblockTypeContradiction */
        if ($commentLinesOfCode < 0) {
            throw new NegativeValueException('$commentLinesOfCode must not be negative');
        }

        /** @psalm-suppress DocblockTypeContradiction */
        if ($nonCommentLinesOfCode < 0) {
            throw new NegativeValueException('$nonCommentLinesOfCode must not be negative');
        }

        /** @psalm-suppress DocblockTypeContradiction */
        if ($logicalLinesOfCode < 0) {
            throw new NegativeValueException('$logicalLinesOfCode must not be negative');
        }

        if ($linesOfCode - $commentLinesOfCode !== $nonCommentLinesOfCode) {
            throw new IllogicalValuesException('$linesOfCode !== $commentLinesOfCode + $nonCommentLinesOfCode');
        }

        $this->linesOfCode           = $linesOfCode;
        $this->commentLinesOfCode    = $commentLinesOfCode;
        $this->nonCommentLinesOfCode = $nonCommentLinesOfCode;
        $this->logicalLinesOfCode    = $logicalLinesOfCode;
    }

    /**
     * @psalm-return non-negative-int
     */
    public function linesOfCode(): int
    {
        return $this->linesOfCode;
    }

    /**
     * @psalm-return non-negative-int
     */
    public function commentLinesOfCode(): int
    {
        return $this->commentLinesOfCode;
    }

    /**
     * @psalm-return non-negative-int
     */
    public function nonCommentLinesOfCode(): int
    {
        return $this->nonCommentLinesOfCode;
    }

    /**
     * @psalm-return non-negative-int
     */
    public function logicalLinesOfCode(): int
    {
        return $this->logicalLinesOfCode;
    }

    public function plus(self $other): self
    {
        return new self(
            $this->linesOfCode() + $other->linesOfCode(),
            $this->commentLinesOfCode() + $other->commentLinesOfCode(),
            $this->nonCommentLinesOfCode() + $other->nonCommentLinesOfCode(),
            $this->logicalLinesOfCode() + $other->logicalLinesOfCode(),
        );
    }
}
