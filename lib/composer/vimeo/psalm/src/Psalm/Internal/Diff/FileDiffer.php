<?php
declare(strict_types=1);
namespace Psalm\Internal\Diff;

use function array_reverse;
use function explode;
use function min;
use function strlen;
use function substr;

/**
 * Borrows from https://github.com/nikic/PHP-Parser/blob/master/lib/PhpParser/Internal/Differ.php
 *
 * Implements the Myers diff algorithm.
 *
 * Myers, Eugene W. "An O (ND) difference algorithm and its variations."
 * Algorithmica 1.1 (1986): 251-266.
 *
 * @internal
 */
class FileDiffer
{
    /**
     * @param list<string>    $a
     * @param list<string>    $b
     *
     * @return array{0:non-empty-list<array<int, int>>, 1: int, 2: int}
     *
     * @psalm-pure
     */
    private static function calculateTrace(
        array $a,
        array $b
    ) : array {
        $n = \count($a);
        $m = \count($b);
        $max = $n + $m;
        $v = [1 => 0];
        $trace = [];
        for ($d = 0; $d <= $max; ++$d) {
            $trace[] = $v;
            for ($k = -$d; $k <= $d; $k += 2) {
                if ($k === -$d || ($k !== $d && $v[$k - 1] < $v[$k + 1])) {
                    $x = $v[$k + 1];
                } else {
                    $x = $v[$k - 1] + 1;
                }

                $y = $x - $k;

                while ($x < $n && $y < $m && $a[$x] === $b[$y]) {
                    ++$x;
                    ++$y;
                }

                $v[$k] = $x;
                if ($x >= $n && $y >= $m) {
                    return [$trace, $x, $y];
                }
            }
        }
        throw new \Exception('Should not happen');
    }

    /**
     * @param list<array<int, int>> $trace
     * @param list<string> $a
     * @param list<string> $b
     *
     * @return list<DiffElem>
     *
     * @psalm-pure
     */
    private static function extractDiff(array $trace, int $x, int $y, array $a, array $b) : array
    {
        $result = [];
        for ($d = \count($trace) - 1; $d >= 0; --$d) {
            $v = $trace[$d];
            $k = $x - $y;

            if ($k === -$d || ($k !== $d && $v[$k - 1] < $v[$k + 1])) {
                $prevK = $k + 1;
            } else {
                $prevK = $k - 1;
            }

            $prevX = $v[$prevK];
            $prevY = $prevX - $prevK;

            while ($x > $prevX && $y > $prevY) {
                $result[] = new DiffElem(
                    DiffElem::TYPE_KEEP,
                    $a[$x - 1],
                    $b[$y - 1]
                );
                --$x;
                --$y;
            }

            if ($d === 0) {
                break;
            }

            while ($x > $prevX) {
                $result[] = new DiffElem(DiffElem::TYPE_REMOVE, $a[$x - 1], null);
                --$x;
            }

            while ($y > $prevY) {
                $result[] = new DiffElem(DiffElem::TYPE_ADD, null, $b[$y - 1]);
                --$y;
            }
        }

        return array_reverse($result);
    }

    /**
     * @return array<int, array{0: int, 1: int, 2: int, 3: int, 4: int, 5: string}>
     *
     * @psalm-pure
     */
    public static function getDiff(string $a_code, string $b_code): array
    {
        $a = explode("\n", $a_code);
        $b = explode("\n", $b_code);
        [$trace, $x, $y] = self::calculateTrace($a, $b);

        $diff = self::coalesceReplacements(self::extractDiff($trace, $x, $y, $a, $b));

        $a_offset = 0;

        $b_offset = 0;

        $last_diff_type = null;

        /** @var array{0:int, 1:int, 2:int, 3:int, 4:int, 5:string}|null */
        $last_change = null;

        $changes = [];
        $i = 0;
        $line_diff = 0;

        foreach ($diff as $diff_elem) {
            $diff_type = $diff_elem->type;

            if ($diff_type !== $last_diff_type) {
                $last_change = null;
            }

            if ($diff_type === DiffElem::TYPE_REMOVE) {
                /** @var string $diff_elem->old */
                $diff_text = $diff_elem->old . "\n";

                $text_length = strlen($diff_text);

                --$line_diff;

                if ($last_change === null) {
                    ++$i;
                    $last_change = [
                        $a_offset,
                        $a_offset + $text_length,
                        $b_offset,
                        $b_offset,
                        $line_diff,
                        '',
                    ];
                    $changes[$i - 1] = $last_change;
                } else {
                    $last_change[1] += $text_length;
                    $last_change[4] = $line_diff;
                    $changes[$i - 1] = $last_change;
                }

                $a_offset += $text_length;
            } elseif ($diff_type === DiffElem::TYPE_ADD) {
                /** @var string $diff_elem->new */
                $diff_text = $diff_elem->new . "\n";

                $text_length = strlen($diff_text);

                ++$line_diff;

                if ($last_change === null) {
                    ++$i;
                    $last_change = [
                        $a_offset,
                        $a_offset,
                        $b_offset,
                        $b_offset + $text_length,
                        $line_diff,
                        $diff_text,
                    ];
                    $changes[$i - 1] = $last_change;
                } else {
                    $last_change[3] += $text_length;
                    $last_change[4] = $line_diff;
                    $last_change[5] .= $diff_text;

                    $changes[$i - 1] = $last_change;
                }

                $b_offset += $text_length;
            } elseif ($diff_type === DiffElem::TYPE_REPLACE) {
                /** @var string $diff_elem->old */
                $old_diff_text = $diff_elem->old . "\n";

                /** @var string $diff_elem->new */
                $new_diff_text = $diff_elem->new . "\n";

                $old_text_length = strlen($old_diff_text);
                $new_text_length = strlen($new_diff_text);

                $max_same_count = min($old_text_length, $new_text_length);

                for ($j = 0; $j < $max_same_count; ++$j) {
                    if ($old_diff_text[$j] !== $new_diff_text[$j]) {
                        break;
                    }

                    ++$a_offset;
                    ++$b_offset;
                    --$old_text_length;
                    --$new_text_length;
                }

                $new_diff_text = substr($new_diff_text, $j);

                if ($last_change === null || $j) {
                    ++$i;
                    $last_change = [
                        $a_offset,
                        $a_offset + $old_text_length,
                        $b_offset,
                        $b_offset + $new_text_length,
                        $line_diff,
                        $new_diff_text,
                    ];
                    $changes[$i - 1] = $last_change;
                } else {
                    $last_change[1] += $old_text_length;
                    $last_change[3] += $new_text_length;
                    $last_change[5] .= $new_diff_text;
                    $changes[$i - 1] = $last_change;
                }

                $a_offset += $old_text_length;
                $b_offset += $new_text_length;
            } else {
                /** @psalm-suppress MixedArgument */
                $same_text_length = strlen($diff_elem->new) + 1;

                $a_offset += $same_text_length;
                $b_offset += $same_text_length;
            }

            $last_diff_type = $diff_elem->type;
        }

        return $changes;
    }

    /**
     * Coalesce equal-length sequences of remove+add into a replace operation.
     *
     * @param DiffElem[] $diff
     *
     * @return list<DiffElem>
     *
     * @psalm-pure
     */
    private static function coalesceReplacements(array $diff): array
    {
        $newDiff = [];
        $c = \count($diff);
        for ($i = 0; $i < $c; ++$i) {
            $diffType = $diff[$i]->type;
            if ($diffType !== DiffElem::TYPE_REMOVE) {
                $newDiff[] = $diff[$i];
                continue;
            }

            $j = $i;
            while ($j < $c && $diff[$j]->type === DiffElem::TYPE_REMOVE) {
                ++$j;
            }

            $k = $j;
            while ($k < $c && $diff[$k]->type === DiffElem::TYPE_ADD) {
                ++$k;
            }

            if ($j - $i === $k - $j) {
                $len = $j - $i;
                for ($n = 0; $n < $len; ++$n) {
                    $newDiff[] = new DiffElem(
                        DiffElem::TYPE_REPLACE,
                        $diff[$i + $n]->old,
                        $diff[$j + $n]->new
                    );
                }
            } else {
                for (; $i < $k; ++$i) {
                    $newDiff[] = $diff[$i];
                }
            }

            $i = $k - 1;
        }

        return $newDiff;
    }
}
