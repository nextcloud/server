<?php
/*
 * This file is part of sebastian/diff.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpCsFixer\Diff\v2_0\Output;

/**
 * Builds a diff string representation in unified diff format in chunks.
 */
final class UnifiedDiffOutputBuilder extends AbstractChunkOutputBuilder
{
    /**
     * @var string
     */
    private $header;

    /**
     * @var bool
     */
    private $addLineNumbers;

    public function __construct($header = "--- Original\n+++ New\n", $addLineNumbers = false)
    {
        $this->header         = $header;
        $this->addLineNumbers = $addLineNumbers;
    }

    public function getDiff(array $diff)
    {
        $buffer = \fopen('php://memory', 'r+b');

        if ('' !== $this->header) {
            \fwrite($buffer, $this->header);
            if ("\n" !== \substr($this->header, -1, 1)) {
                \fwrite($buffer, "\n");
            }
        }

        $this->writeDiffChunked($buffer, $diff, $this->getCommonChunks($diff));

        $diff = \stream_get_contents($buffer, -1, 0);

        \fclose($buffer);

        return $diff;
    }

    // `old` is an array with key => value pairs . Each pair represents a start and end index of `diff`
    // of a list of elements all containing `same` (0) entries.
    private function writeDiffChunked($output, array $diff, array $old)
    {
        $upperLimit = \count($diff);
        $start      = 0;
        $fromStart  = 0;
        $toStart    = 0;

        if (\count($old)) { // no common parts, list all diff entries
            \reset($old);

            // iterate the diff, go from chunk to chunk skipping common chunk of lines between those
            do {
                $commonStart = \key($old);
                $commonEnd   = \current($old);

                if ($commonStart !== $start) {
                    list($fromRange, $toRange) = $this->getChunkRange($diff, $start, $commonStart);
                    $this->writeChunk($output, $diff, $start, $commonStart, $fromStart, $fromRange, $toStart, $toRange);

                    $fromStart += $fromRange;
                    $toStart += $toRange;
                }

                $start        = $commonEnd + 1;
                $commonLength = $commonEnd - $commonStart + 1; // calculate number of non-change lines in the common part
                $fromStart += $commonLength;
                $toStart += $commonLength;
            } while (false !== \next($old));

            \end($old); // short cut for finding possible last `change entry`
            $tmp = \key($old);
            \reset($old);
            if ($old[$tmp] === $upperLimit - 1) {
                $upperLimit = $tmp;
            }
        }

        if ($start < $upperLimit - 1) { // check for trailing (non) diff entries
            do {
                --$upperLimit;
            } while (isset($diff[$upperLimit][1]) && $diff[$upperLimit][1] === 0);
            ++$upperLimit;

            list($fromRange, $toRange) = $this->getChunkRange($diff, $start, $upperLimit);
            $this->writeChunk($output, $diff, $start, $upperLimit, $fromStart, $fromRange, $toStart, $toRange);
        }
    }

    private function writeChunk(
        $output,
        array $diff,
        $diffStartIndex,
        $diffEndIndex,
        $fromStart,
        $fromRange,
        $toStart,
        $toRange
    ) {
        if ($this->addLineNumbers) {
            \fwrite($output, '@@ -' . (1 + $fromStart));

            if ($fromRange !== 1) {
                \fwrite($output, ',' . $fromRange);
            }

            \fwrite($output, ' +' . (1 + $toStart));
            if ($toRange !== 1) {
                \fwrite($output, ',' . $toRange);
            }

            \fwrite($output, " @@\n");
        } else {
            \fwrite($output, "@@ @@\n");
        }

        for ($i = $diffStartIndex; $i < $diffEndIndex; ++$i) {
            if ($diff[$i][1] === 1 /* ADDED */) {
                \fwrite($output, '+' . $diff[$i][0]);
            } elseif ($diff[$i][1] === 2 /* REMOVED */) {
                \fwrite($output, '-' . $diff[$i][0]);
            } else { /* Not changed (old) 0 or Warning 3 */
                \fwrite($output, ' ' . $diff[$i][0]);
            }

            $lc = \substr($diff[$i][0], -1);
            if ($lc !== "\n" && $lc !== "\r") {
                \fwrite($output, "\n"); // \No newline at end of file
            }
        }
    }

    private function getChunkRange(array $diff, $diffStartIndex, $diffEndIndex)
    {
        $toRange   = 0;
        $fromRange = 0;

        for ($i = $diffStartIndex; $i < $diffEndIndex; ++$i) {
            if ($diff[$i][1] === 1) { // added
                ++$toRange;
            } elseif ($diff[$i][1] === 2) { // removed
                ++$fromRange;
            } elseif ($diff[$i][1] === 0) { // same
                ++$fromRange;
                ++$toRange;
            }
        }

        return [$fromRange, $toRange];
    }
}
