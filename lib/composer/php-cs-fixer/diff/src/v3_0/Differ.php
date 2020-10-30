<?php
/*
 * This file is part of sebastian/diff.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpCsFixer\Diff\v3_0;

use PhpCsFixer\Diff\v3_0\Output\DiffOutputBuilderInterface;
use PhpCsFixer\Diff\v3_0\Output\UnifiedDiffOutputBuilder;

/**
 * Diff implementation.
 */
final class Differ
{
    const OLD                     = 0;
    const ADDED                   = 1;
    const REMOVED                 = 2;
    const DIFF_LINE_END_WARNING   = 3;
    const NO_LINE_END_EOF_WARNING = 4;

    /**
     * @var DiffOutputBuilderInterface
     */
    private $outputBuilder;

    /**
     * @param DiffOutputBuilderInterface $outputBuilder
     *
     * @throws InvalidArgumentException
     */
    public function __construct($outputBuilder = null)
    {
        if ($outputBuilder instanceof DiffOutputBuilderInterface) {
            $this->outputBuilder = $outputBuilder;
        } elseif (null === $outputBuilder) {
            $this->outputBuilder = new UnifiedDiffOutputBuilder;
        } elseif (\is_string($outputBuilder)) {
            // PHPUnit 6.1.4, 6.2.0, 6.2.1, 6.2.2, and 6.2.3 support
            // @see https://github.com/sebastianbergmann/phpunit/issues/2734#issuecomment-314514056
            // @deprecated
            $this->outputBuilder = new UnifiedDiffOutputBuilder($outputBuilder);
        } else {
            throw new InvalidArgumentException(
                \sprintf(
                    'Expected builder to be an instance of DiffOutputBuilderInterface, <null> or a string, got %s.',
                    \is_object($outputBuilder) ? 'instance of "' . \get_class($outputBuilder) . '"' : \gettype($outputBuilder) . ' "' . $outputBuilder . '"'
                )
            );
        }
    }

    /**
     * Returns the diff between two arrays or strings as string.
     *
     * @param array|string                            $from
     * @param array|string                            $to
     * @param null|LongestCommonSubsequenceCalculator $lcs
     *
     * @return string
     */
    public function diff($from, $to, LongestCommonSubsequenceCalculator $lcs = null)
    {
        $diff = $this->diffToArray(
            $this->normalizeDiffInput($from),
            $this->normalizeDiffInput($to),
            $lcs
        );

        return $this->outputBuilder->getDiff($diff);
    }

    /**
     * Returns the diff between two arrays or strings as array.
     *
     * Each array element contains two elements:
     *   - [0] => mixed $token
     *   - [1] => 2|1|0
     *
     * - 2: REMOVED: $token was removed from $from
     * - 1: ADDED: $token was added to $from
     * - 0: OLD: $token is not changed in $to
     *
     * @param array|string                       $from
     * @param array|string                       $to
     * @param LongestCommonSubsequenceCalculator $lcs
     *
     * @return array
     */
    public function diffToArray($from, $to, LongestCommonSubsequenceCalculator $lcs = null)
    {
        if (\is_string($from)) {
            $from = $this->splitStringByLines($from);
        } elseif (!\is_array($from)) {
            throw new InvalidArgumentException('"from" must be an array or string.');
        }

        if (\is_string($to)) {
            $to = $this->splitStringByLines($to);
        } elseif (!\is_array($to)) {
            throw new InvalidArgumentException('"to" must be an array or string.');
        }

        list($from, $to, $start, $end) = self::getArrayDiffParted($from, $to);

        if ($lcs === null) {
            $lcs = $this->selectLcsImplementation($from, $to);
        }

        $common = $lcs->calculate(\array_values($from), \array_values($to));
        $diff   = [];

        foreach ($start as $token) {
            $diff[] = [$token, self::OLD];
        }

        \reset($from);
        \reset($to);

        foreach ($common as $token) {
            while (($fromToken = \reset($from)) !== $token) {
                $diff[] = [\array_shift($from), self::REMOVED];
            }

            while (($toToken = \reset($to)) !== $token) {
                $diff[] = [\array_shift($to), self::ADDED];
            }

            $diff[] = [$token, self::OLD];

            \array_shift($from);
            \array_shift($to);
        }

        while (($token = \array_shift($from)) !== null) {
            $diff[] = [$token, self::REMOVED];
        }

        while (($token = \array_shift($to)) !== null) {
            $diff[] = [$token, self::ADDED];
        }

        foreach ($end as $token) {
            $diff[] = [$token, self::OLD];
        }

        if ($this->detectUnmatchedLineEndings($diff)) {
            \array_unshift($diff, ["#Warnings contain different line endings!\n", self::DIFF_LINE_END_WARNING]);
        }

        return $diff;
    }

    /**
     * Casts variable to string if it is not a string or array.
     *
     * @param mixed $input
     *
     * @return array|string
     */
    private function normalizeDiffInput($input)
    {
        if (!\is_array($input) && !\is_string($input)) {
            return (string) $input;
        }

        return $input;
    }

    /**
     * Checks if input is string, if so it will split it line-by-line.
     *
     * @param string $input
     *
     * @return array
     */
    private function splitStringByLines($input)
    {
        return \preg_split('/(.*\R)/', $input, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    }

    /**
     * @param array $from
     * @param array $to
     *
     * @return LongestCommonSubsequenceCalculator
     */
    private function selectLcsImplementation(array $from, array $to)
    {
        // We do not want to use the time-efficient implementation if its memory
        // footprint will probably exceed this value. Note that the footprint
        // calculation is only an estimation for the matrix and the LCS method
        // will typically allocate a bit more memory than this.
        $memoryLimit = 100 * 1024 * 1024;

        if ($this->calculateEstimatedFootprint($from, $to) > $memoryLimit) {
            return new MemoryEfficientLongestCommonSubsequenceCalculator;
        }

        return new TimeEfficientLongestCommonSubsequenceCalculator;
    }

    /**
     * Calculates the estimated memory footprint for the DP-based method.
     *
     * @param array $from
     * @param array $to
     *
     * @return float|int
     */
    private function calculateEstimatedFootprint(array $from, array $to)
    {
        $itemSize = PHP_INT_SIZE === 4 ? 76 : 144;

        return $itemSize * \min(\count($from), \count($to)) ** 2;
    }

    /**
     * Returns true if line ends don't match in a diff.
     *
     * @param array $diff
     *
     * @return bool
     */
    private function detectUnmatchedLineEndings(array $diff)
    {
        $newLineBreaks = ['' => true];
        $oldLineBreaks = ['' => true];

        foreach ($diff as $entry) {
            if (self::OLD === $entry[1]) {
                $ln                 = $this->getLinebreak($entry[0]);
                $oldLineBreaks[$ln] = true;
                $newLineBreaks[$ln] = true;
            } elseif (self::ADDED === $entry[1]) {
                $newLineBreaks[$this->getLinebreak($entry[0])] = true;
            } elseif (self::REMOVED === $entry[1]) {
                $oldLineBreaks[$this->getLinebreak($entry[0])] = true;
            }
        }

        // if either input or output is a single line without breaks than no warning should be raised
        if (['' => true] === $newLineBreaks || ['' => true] === $oldLineBreaks) {
            return false;
        }

        // two way compare
        foreach ($newLineBreaks as $break => $set) {
            if (!isset($oldLineBreaks[$break])) {
                return true;
            }
        }

        foreach ($oldLineBreaks as $break => $set) {
            if (!isset($newLineBreaks[$break])) {
                return true;
            }
        }

        return false;
    }

    private function getLinebreak($line)
    {
        if (!\is_string($line)) {
            return '';
        }

        $lc = \substr($line, -1);
        if ("\r" === $lc) {
            return "\r";
        }

        if ("\n" !== $lc) {
            return '';
        }

        if ("\r\n" === \substr($line, -2)) {
            return "\r\n";
        }

        return "\n";
    }

    private static function getArrayDiffParted(array &$from, array &$to)
    {
        $start = [];
        $end   = [];

        \reset($to);

        foreach ($from as $k => $v) {
            $toK = \key($to);

            if ($toK === $k && $v === $to[$k]) {
                $start[$k] = $v;

                unset($from[$k], $to[$k]);
            } else {
                break;
            }
        }

        \end($from);
        \end($to);

        do {
            $fromK = \key($from);
            $toK   = \key($to);

            if (null === $fromK || null === $toK || \current($from) !== \current($to)) {
                break;
            }

            \prev($from);
            \prev($to);

            $end = [$fromK => $from[$fromK]] + $end;
            unset($from[$fromK], $to[$toK]);
        } while (true);

        return [$from, $to, $start, $end];
    }
}
