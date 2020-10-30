<?php
namespace Psalm\Internal\Diff;

use function array_merge;
use function end;
use function get_class;
use PhpParser;
use function substr;

/**
 * @internal
 */
class NamespaceStatementsDiffer extends AstDiffer
{
    /**
     * Calculate diff (edit script) from $a to $b.
     * @param array<int, PhpParser\Node\Stmt> $a
     * @param array<int, PhpParser\Node\Stmt> $b
     *
     * @return array{
     *      0: list<string>,
     *      1: list<string>,
     *      2: list<string>,
     *      3: list<array{0: int, 1: int, 2: int, 3: int}>
     * }
     */
    public static function diff(string $name, array $a, array $b, string $a_code, string $b_code): array
    {
        [$trace, $x, $y, $bc] = self::calculateTrace(
            /**
             * @param string $a_code
             * @param string $b_code
             *
             * @return bool
             */
            function (
                PhpParser\Node\Stmt $a,
                PhpParser\Node\Stmt $b,
                $a_code,
                $b_code,
                bool &$body_change = false
            ): bool {
                if (get_class($a) !== get_class($b)) {
                    return false;
                }

                if (($a instanceof PhpParser\Node\Stmt\Class_ && $b instanceof PhpParser\Node\Stmt\Class_)
                    || ($a instanceof PhpParser\Node\Stmt\Interface_ && $b instanceof PhpParser\Node\Stmt\Interface_)
                    || ($a instanceof PhpParser\Node\Stmt\Trait_ && $b instanceof PhpParser\Node\Stmt\Trait_)
                ) {
                    // @todo add check for comments comparison

                    return (string)$a->name === (string)$b->name;
                }

                if (($a instanceof PhpParser\Node\Stmt\Use_
                        && $b instanceof PhpParser\Node\Stmt\Use_)
                    || ($a instanceof PhpParser\Node\Stmt\GroupUse
                        && $b instanceof PhpParser\Node\Stmt\GroupUse)
                ) {
                    $a_start = (int)$a->getAttribute('startFilePos');
                    $a_end = (int)$a->getAttribute('endFilePos');

                    $b_start = (int)$b->getAttribute('startFilePos');
                    $b_end = (int)$b->getAttribute('endFilePos');

                    $a_size = $a_end - $a_start;
                    $b_size = $b_end - $b_start;

                    if (substr($a_code, $a_start, $a_size) === substr($b_code, $b_start, $b_size)) {
                        return true;
                    }
                }

                return false;
            },
            $a,
            $b,
            $a_code,
            $b_code
        );

        $diff = self::extractDiff($trace, $x, $y, $a, $b, $bc);

        $keep = [];
        $keep_signature = [];
        $add_or_delete = [];
        $diff_map = [];

        foreach ($diff as $diff_elem) {
            if ($diff_elem->type === DiffElem::TYPE_KEEP) {
                if (($diff_elem->old instanceof PhpParser\Node\Stmt\Class_
                        && $diff_elem->new instanceof PhpParser\Node\Stmt\Class_)
                    || ($diff_elem->old instanceof PhpParser\Node\Stmt\Interface_
                        && $diff_elem->new instanceof PhpParser\Node\Stmt\Interface_)
                    || ($diff_elem->old instanceof PhpParser\Node\Stmt\Trait_
                        && $diff_elem->new instanceof PhpParser\Node\Stmt\Trait_)
                ) {
                    $class_keep = ClassStatementsDiffer::diff(
                        ($name ? $name . '\\' : '') . $diff_elem->old->name,
                        $diff_elem->old->stmts,
                        $diff_elem->new->stmts,
                        $a_code,
                        $b_code
                    );

                    $keep = array_merge($keep, $class_keep[0]);
                    $keep_signature = array_merge($keep_signature, $class_keep[1]);
                    $add_or_delete = array_merge($add_or_delete, $class_keep[2]);
                    $diff_map = array_merge($diff_map, $class_keep[3]);
                }
            } elseif ($diff_elem->type === DiffElem::TYPE_REMOVE) {
                if ($diff_elem->old instanceof PhpParser\Node\Stmt\Use_
                    || $diff_elem->old instanceof PhpParser\Node\Stmt\GroupUse
                ) {
                    foreach ($diff_elem->old->uses as $use) {
                        if ($use->alias) {
                            $add_or_delete[] = 'use:' . (string) $use->alias;
                        } else {
                            $name_parts = $use->name->parts;

                            $add_or_delete[] = 'use:' . end($name_parts);
                        }
                    }
                }
            } elseif ($diff_elem->type === DiffElem::TYPE_ADD) {
                if ($diff_elem->new instanceof PhpParser\Node\Stmt\Use_
                    || $diff_elem->new instanceof PhpParser\Node\Stmt\GroupUse
                ) {
                    foreach ($diff_elem->new->uses as $use) {
                        if ($use->alias) {
                            $add_or_delete[] = 'use:' . (string) $use->alias;
                        } else {
                            $name_parts = $use->name->parts;

                            $add_or_delete[] = 'use:' . end($name_parts);
                        }
                    }
                }
            }
        }

        return [$keep, $keep_signature, $add_or_delete, $diff_map];
    }
}
