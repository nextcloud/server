<?php declare(strict_types=1);

namespace PhpParser\Internal;

/**
 * Implements the Myers diff algorithm.
 *
 * Myers, Eugene W. "An O (ND) difference algorithm and its variations."
 * Algorithmica 1.1 (1986): 251-266.
 *
 * @internal
 */
class Differ
{
    private $isEqual;

    /**
     * Create differ over the given equality relation.
     *
     * @param callable $isEqual Equality relation with signature function($a, $b) : bool
     */
    public function __construct(callable $isEqual) {
        $this->isEqual = $isEqual;
    }

    /**
     * Calculate diff (edit script) from $old to $new.
     *
     * @param array $old Original array
     * @param array $new New array
     *
     * @return DiffElem[] Diff (edit script)
     */
    public function diff(array $old, array $new) {
        list($trace, $x, $y) = $this->calculateTrace($old, $new);
        return $this->extractDiff($trace, $x, $y, $old, $new);
    }

    /**
     * Calculate diff, including "replace" operations.
     *
     * If a sequence of remove operations is followed by the same number of add operations, these
     * will be coalesced into replace operations.
     *
     * @param array $old Original array
     * @param array $new New array
     *
     * @return DiffElem[] Diff (edit script), including replace operations
     */
    public function diffWithReplacements(array $old, array $new) {
        return $this->coalesceReplacements($this->diff($old, $new));
    }

    private function calculateTrace(array $a, array $b) {
        $n = \count($a);
        $m = \count($b);
        $max = $n + $m;
        $v = [1 => 0];
        $trace = [];
        for ($d = 0; $d <= $max; $d++) {
            $trace[] = $v;
            for ($k = -$d; $k <= $d; $k += 2) {
                if ($k === -$d || ($k !== $d && $v[$k-1] < $v[$k+1])) {
                    $x = $v[$k+1];
                } else {
                    $x = $v[$k-1] + 1;
                }

                $y = $x - $k;
                while ($x < $n && $y < $m && ($this->isEqual)($a[$x], $b[$y])) {
                    $x++;
                    $y++;
                }

                $v[$k] = $x;
                if ($x >= $n && $y >= $m) {
                    return [$trace, $x, $y];
                }
            }
        }
        throw new \Exception('Should not happen');
    }

    private function extractDiff(array $trace, int $x, int $y, array $a, array $b) {
        $result = [];
        for ($d = \count($trace) - 1; $d >= 0; $d--) {
            $v = $trace[$d];
            $k = $x - $y;

            if ($k === -$d || ($k !== $d && $v[$k-1] < $v[$k+1])) {
                $prevK = $k + 1;
            } else {
                $prevK = $k - 1;
            }

            $prevX = $v[$prevK];
            $prevY = $prevX - $prevK;

            while ($x > $prevX && $y > $prevY) {
                $result[] = new DiffElem(DiffElem::TYPE_KEEP, $a[$x-1], $b[$y-1]);
                $x--;
                $y--;
            }

            if ($d === 0) {
                break;
            }

            while ($x > $prevX) {
                $result[] = new DiffElem(DiffElem::TYPE_REMOVE, $a[$x-1], null);
                $x--;
            }

            while ($y > $prevY) {
                $result[] = new DiffElem(DiffElem::TYPE_ADD, null, $b[$y-1]);
                $y--;
            }
        }
        return array_reverse($result);
    }

    /**
     * Coalesce equal-length sequences of remove+add into a replace operation.
     *
     * @param DiffElem[] $diff
     * @return DiffElem[]
     */
    private function coalesceReplacements(array $diff) {
        $newDiff = [];
        $c = \count($diff);
        for ($i = 0; $i < $c; $i++) {
            $diffType = $diff[$i]->type;
            if ($diffType !== DiffElem::TYPE_REMOVE) {
                $newDiff[] = $diff[$i];
                continue;
            }

            $j = $i;
            while ($j < $c && $diff[$j]->type === DiffElem::TYPE_REMOVE) {
                $j++;
            }

            $k = $j;
            while ($k < $c && $diff[$k]->type === DiffElem::TYPE_ADD) {
                $k++;
            }

            if ($j - $i === $k - $j) {
                $len = $j - $i;
                for ($n = 0; $n < $len; $n++) {
                    $newDiff[] = new DiffElem(
                        DiffElem::TYPE_REPLACE, $diff[$i + $n]->old, $diff[$j + $n]->new
                    );
                }
            } else {
                for (; $i < $k; $i++) {
                    $newDiff[] = $diff[$i];
                }
            }
            $i = $k - 1;
        }
        return $newDiff;
    }
}
