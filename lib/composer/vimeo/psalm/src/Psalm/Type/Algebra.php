<?php
namespace Psalm\Type;

use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use PhpParser;
use Psalm\Codebase;
use Psalm\Exception\ComplicatedExpressionException;
use Psalm\FileSource;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;
use Psalm\Internal\Clause;
use function strlen;
use function substr;

class Algebra
{
    /**
     * @param array<string, non-empty-list<non-empty-list<string>>>  $all_types
     *
     * @return array<string, non-empty-list<non-empty-list<string>>>
     *
     * @psalm-pure
     */
    public static function negateTypes(array $all_types): array
    {
        return array_filter(
            array_map(
                /**
                 * @param  non-empty-list<non-empty-list<string>> $anded_types
                 *
                 * @return list<non-empty-list<string>>
                 */
                function (array $anded_types): array {
                    if (count($anded_types) > 1) {
                        $new_anded_types = [];

                        foreach ($anded_types as $orred_types) {
                            if (count($orred_types) > 1) {
                                return [];
                            }

                            $new_anded_types[] = self::negateType($orred_types[0]);
                        }

                        return [$new_anded_types];
                    }

                    $new_orred_types = [];

                    foreach ($anded_types[0] as $orred_type) {
                        $new_orred_types[] = [self::negateType($orred_type)];
                    }

                    return $new_orred_types;
                },
                $all_types
            )
        );
    }

    /**
     * @psalm-pure
     */
    public static function negateType(string $type): string
    {
        if ($type === 'mixed') {
            return $type;
        }

        return $type[0] === '!' ? substr($type, 1) : '!' . $type;
    }

    /**
     * @return list<Clause>
     */
    public static function getFormula(
        int $conditional_object_id,
        int $creating_object_id,
        PhpParser\Node\Expr $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase = null,
        bool $inside_negation = false,
        bool $cache = true
    ): array {
        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
        ) {
            $left_assertions = self::getFormula(
                $conditional_object_id,
                \spl_object_id($conditional->left),
                $conditional->left,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache
            );

            $right_assertions = self::getFormula(
                $conditional_object_id,
                \spl_object_id($conditional->right),
                $conditional->right,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache
            );

            return array_merge(
                $left_assertions,
                $right_assertions
            );
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
        ) {
            $left_clauses = self::getFormula(
                $conditional_object_id,
                \spl_object_id($conditional->left),
                $conditional->left,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache
            );

            $right_clauses = self::getFormula(
                $conditional_object_id,
                \spl_object_id($conditional->right),
                $conditional->right,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache
            );

            return self::combineOredClauses($left_clauses, $right_clauses, $conditional_object_id);
        }

        if ($conditional instanceof PhpParser\Node\Expr\BooleanNot) {
            if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
                $and_expr = new PhpParser\Node\Expr\BinaryOp\BooleanAnd(
                    new PhpParser\Node\Expr\BooleanNot(
                        $conditional->expr->left,
                        $conditional->getAttributes()
                    ),
                    new PhpParser\Node\Expr\BooleanNot(
                        $conditional->expr->right,
                        $conditional->getAttributes()
                    ),
                    $conditional->expr->getAttributes()
                );

                return self::getFormula(
                    $conditional_object_id,
                    $conditional_object_id,
                    $and_expr,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    false
                );
            }

            if ($conditional->expr instanceof PhpParser\Node\Expr\Isset_
                && count($conditional->expr->vars) > 1
            ) {
                $assertions = null;

                if ($cache && $source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
                    $assertions = $source->node_data->getAssertions($conditional->expr);
                }

                if ($assertions === null) {
                    $assertions = AssertionFinder::scrapeAssertions(
                        $conditional->expr,
                        $this_class_name,
                        $source,
                        $codebase,
                        $inside_negation,
                        $cache
                    );

                    if ($cache && $source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
                        $source->node_data->setAssertions($conditional->expr, $assertions);
                    }
                }

                $clauses = [];

                foreach ($assertions as $var => $anded_types) {
                    $redefined = false;

                    if ($var[0] === '=') {
                        /** @var string */
                        $var = substr($var, 1);
                        $redefined = true;
                    }

                    foreach ($anded_types as $orred_types) {
                        $clauses[] = new Clause(
                            [$var => $orred_types],
                            $conditional_object_id,
                            \spl_object_id($conditional->expr),
                            false,
                            true,
                            $orred_types[0][0] === '='
                                || $orred_types[0][0] === '~'
                                || (strlen($orred_types[0]) > 1
                                    && ($orred_types[0][1] === '='
                                        || $orred_types[0][1] === '~')),
                            $redefined ? [$var => true] : []
                        );
                    }
                }

                return self::negateFormula($clauses);
            }

            if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
                $and_expr = new PhpParser\Node\Expr\BinaryOp\BooleanOr(
                    new PhpParser\Node\Expr\BooleanNot(
                        $conditional->expr->left,
                        $conditional->getAttributes()
                    ),
                    new PhpParser\Node\Expr\BooleanNot(
                        $conditional->expr->right,
                        $conditional->getAttributes()
                    ),
                    $conditional->expr->getAttributes()
                );

                return self::getFormula(
                    $conditional_object_id,
                    \spl_object_id($conditional->expr),
                    $and_expr,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    false
                );
            }

            return self::negateFormula(
                self::getFormula(
                    $conditional_object_id,
                    \spl_object_id($conditional->expr),
                    $conditional->expr,
                    $this_class_name,
                    $source,
                    $codebase,
                    !$inside_negation
                )
            );
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
        ) {
            $false_pos = AssertionFinder::hasFalseVariable($conditional);
            $true_pos = AssertionFinder::hasTrueVariable($conditional);

            if ($false_pos === AssertionFinder::ASSIGNMENT_TO_RIGHT
                && ($conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr)
            ) {
                $inside_negation = !$inside_negation;

                return self::getFormula(
                    $conditional_object_id,
                    \spl_object_id($conditional->left),
                    $conditional->left,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    $cache
                );
            }

            if ($false_pos === AssertionFinder::ASSIGNMENT_TO_LEFT
                && ($conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr)
            ) {
                $inside_negation = !$inside_negation;

                return self::getFormula(
                    $conditional_object_id,
                    \spl_object_id($conditional->right),
                    $conditional->right,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    $cache
                );
            }

            if ($true_pos === AssertionFinder::ASSIGNMENT_TO_RIGHT
                && ($conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr)
            ) {
                return self::getFormula(
                    $conditional_object_id,
                    \spl_object_id($conditional->left),
                    $conditional->left,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    $cache
                );
            }

            if ($true_pos === AssertionFinder::ASSIGNMENT_TO_LEFT
                && ($conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr)
            ) {
                return self::getFormula(
                    $conditional_object_id,
                    \spl_object_id($conditional->right),
                    $conditional->right,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    $cache
                );
            }
        }

        if ($conditional instanceof PhpParser\Node\Expr\Cast\Bool_) {
            return self::getFormula(
                $conditional_object_id,
                \spl_object_id($conditional->expr),
                $conditional->expr,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache
            );
        }

        $assertions = null;

        if ($cache && $source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
            $assertions = $source->node_data->getAssertions($conditional);
        }

        if ($assertions === null) {
            $assertions = AssertionFinder::scrapeAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache
            );

            if ($cache && $source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
                $source->node_data->setAssertions($conditional, $assertions);
            }
        }

        if ($assertions) {
            $clauses = [];

            foreach ($assertions as $var => $anded_types) {
                $redefined = false;

                if ($var[0] === '=') {
                    /** @var string */
                    $var = substr($var, 1);
                    $redefined = true;
                }

                foreach ($anded_types as $orred_types) {
                    $clauses[] = new Clause(
                        [$var => $orred_types],
                        $conditional_object_id,
                        $creating_object_id,
                        false,
                        true,
                        $orred_types[0][0] === '='
                            || $orred_types[0][0] === '~'
                            || (strlen($orred_types[0]) > 1
                                && ($orred_types[0][1] === '='
                                    || $orred_types[0][1] === '~')),
                        $redefined ? [$var => true] : []
                    );
                }
            }

            return $clauses;
        }

        return [new Clause([], $conditional_object_id, $creating_object_id, true)];
    }

    /**
     * This is a very simple simplification heuristic
     * for CNF formulae.
     *
     * It simplifies formulae:
     *     ($a) && ($a || $b) => $a
     *     (!$a) && (!$b) && ($a || $b || $c) => $c
     *
     * @param list<Clause>  $clauses
     *
     * @return list<Clause>
     *
     * @psalm-pure
     */
    public static function simplifyCNF(array $clauses): array
    {
        $cloned_clauses = [];

        // avoid strict duplicates
        foreach ($clauses as $clause) {
            $unique_clause = $clause->makeUnique();
            $cloned_clauses[$unique_clause->hash] = $unique_clause;
        }

        // remove impossible types
        foreach ($cloned_clauses as $clause_a) {
            if (count($clause_a->possibilities) !== 1 || count(array_values($clause_a->possibilities)[0]) !== 1) {
                continue;
            }

            if (!$clause_a->reconcilable || $clause_a->wedge) {
                continue;
            }

            $clause_var = array_keys($clause_a->possibilities)[0];
            $only_type = array_pop(array_values($clause_a->possibilities)[0]);
            $negated_clause_type = self::negateType($only_type);

            foreach ($cloned_clauses as $clause_hash => $clause_b) {
                if ($clause_a === $clause_b || !$clause_b->reconcilable || $clause_b->wedge) {
                    continue;
                }

                if (isset($clause_b->possibilities[$clause_var]) &&
                    in_array($negated_clause_type, $clause_b->possibilities[$clause_var], true)
                ) {
                    $clause_var_possibilities = array_values(
                        array_filter(
                            $clause_b->possibilities[$clause_var],
                            function (string $possible_type) use ($negated_clause_type): bool {
                                return $possible_type !== $negated_clause_type;
                            }
                        )
                    );

                    unset($cloned_clauses[$clause_hash]);

                    if (!$clause_var_possibilities) {
                        $updated_clause = $clause_b->removePossibilities($clause_var);

                        if ($updated_clause) {
                            $cloned_clauses[$updated_clause->hash] = $updated_clause;
                        }
                    } else {
                        $updated_clause = $clause_b->addPossibilities(
                            $clause_var,
                            $clause_var_possibilities
                        );

                        $cloned_clauses[$updated_clause->hash] = $updated_clause;
                    }
                }
            }
        }

        $simplified_clauses = [];

        foreach ($cloned_clauses as $clause_a) {
            $is_redundant = false;

            foreach ($cloned_clauses as $clause_b) {
                if ($clause_a === $clause_b
                    || !$clause_b->reconcilable
                    || $clause_b->wedge
                    || $clause_a->wedge
                ) {
                    continue;
                }

                if ($clause_a->contains($clause_b)) {
                    $is_redundant = true;
                    break;
                }
            }

            if (!$is_redundant) {
                $simplified_clauses[] = $clause_a;
            }
        }

        return $simplified_clauses;
    }

    /**
     * Look for clauses with only one possible value
     *
     * @param  list<Clause>  $clauses
     * @param  array<string, bool> $cond_referenced_var_ids
     * @param  array<string, array<int, array<int, string>>> $active_truths
     *
     * @return array<string, list<array<int, string>>>
     */
    public static function getTruthsFromFormula(
        array $clauses,
        ?int $creating_conditional_id = null,
        array &$cond_referenced_var_ids = [],
        array &$active_truths = []
    ): array {
        $truths = [];
        $active_truths = [];

        if ($clauses === []) {
            return [];
        }

        foreach ($clauses as $clause) {
            if (!$clause->reconcilable) {
                continue;
            }

            foreach ($clause->possibilities as $var => $possible_types) {
                // if there's only one possible type, return it
                if (count($clause->possibilities) === 1 && count($possible_types) === 1) {
                    $possible_type = array_pop($possible_types);

                    if (isset($truths[$var]) && !isset($clause->redefined_vars[$var])) {
                        $truths[$var][] = [$possible_type];
                    } else {
                        $truths[$var] = [[$possible_type]];
                    }

                    if ($creating_conditional_id && $creating_conditional_id === $clause->creating_conditional_id) {
                        if (!isset($active_truths[$var])) {
                            $active_truths[$var] = [];
                        }

                        $active_truths[$var][count($truths[$var]) - 1] = [$possible_type];
                    }
                } elseif (count($clause->possibilities) === 1) {
                    // if there's only one active clause, return all the non-negation clause members ORed together
                    $things_that_can_be_said = array_filter(
                        $possible_types,
                        function (string $possible_type): bool {
                            return $possible_type[0] !== '!';
                        }
                    );

                    if ($things_that_can_be_said && count($things_that_can_be_said) === count($possible_types)) {
                        $things_that_can_be_said = array_unique($things_that_can_be_said);

                        if ($clause->generated && count($possible_types) > 1) {
                            unset($cond_referenced_var_ids[$var]);
                        }

                        /** @var array<int, string> $things_that_can_be_said */
                        $truths[$var] = [$things_that_can_be_said];

                        if ($creating_conditional_id && $creating_conditional_id === $clause->creating_conditional_id) {
                            $active_truths[$var] = [$things_that_can_be_said];
                        }
                    }
                }
            }
        }

        return $truths;
    }

    /**
     * @param non-empty-list<Clause>  $clauses
     *
     * @return list<Clause>
     *
     * @psalm-pure
     */
    public static function groupImpossibilities(array $clauses): array
    {
        $complexity = 1;

        $seed_clauses = [];

        $clause = array_pop($clauses);

        if (!$clause->wedge) {
            if ($clause->impossibilities === null) {
                throw new \UnexpectedValueException('$clause->impossibilities should not be null');
            }

            foreach ($clause->impossibilities as $var => $impossible_types) {
                foreach ($impossible_types as $impossible_type) {
                    $seed_clause = new Clause(
                        [$var => [$impossible_type]],
                        $clause->creating_conditional_id,
                        $clause->creating_object_id
                    );

                    $seed_clauses[] = $seed_clause;

                    ++$complexity;
                }
            }
        }

        if (!$clauses || !$seed_clauses) {
            return $seed_clauses;
        }

        while ($clauses) {
            $clause = array_pop($clauses);

            $new_clauses = [];

            foreach ($seed_clauses as $grouped_clause) {
                if ($clause->impossibilities === null) {
                    throw new \UnexpectedValueException('$clause->impossibilities should not be null');
                }

                foreach ($clause->impossibilities as $var => $impossible_types) {
                    foreach ($impossible_types as $impossible_type) {
                        $new_clause_possibilities = $grouped_clause->possibilities;

                        if (isset($grouped_clause->possibilities[$var])) {
                            $new_clause_possibilities[$var][] = $impossible_type;
                        } else {
                            $new_clause_possibilities[$var] = [$impossible_type];
                        }

                        $new_clause = new Clause(
                            $new_clause_possibilities,
                            $grouped_clause->creating_conditional_id,
                            $clause->creating_object_id,
                            false,
                            true,
                            true,
                            []
                        );

                        $new_clauses[] = $new_clause;

                        ++$complexity;

                        if ($complexity > 20000) {
                            throw new ComplicatedExpressionException();
                        }
                    }
                }
            }

            $seed_clauses = $new_clauses;
        }

        return $seed_clauses;
    }

    /**
     * @param list<Clause>  $left_clauses
     * @param list<Clause>  $right_clauses
     *
     * @return list<Clause>
     *
     * @psalm-pure
     */
    public static function combineOredClauses(
        array $left_clauses,
        array $right_clauses,
        int $conditional_object_id
    ): array {
        $clauses = [];

        $all_wedges = true;
        $has_wedge = false;

        foreach ($left_clauses as $left_clause) {
            foreach ($right_clauses as $right_clause) {
                $all_wedges = $all_wedges && ($left_clause->wedge && $right_clause->wedge);
                $has_wedge = $has_wedge || ($left_clause->wedge && $right_clause->wedge);
            }
        }

        if ($all_wedges) {
            return [new Clause([], $conditional_object_id, $conditional_object_id, true)];
        }

        foreach ($left_clauses as $left_clause) {
            foreach ($right_clauses as $right_clause) {
                if ($left_clause->wedge && $right_clause->wedge) {
                    // handled below
                    continue;
                }

                /** @var  array<string, non-empty-list<string>> */
                $possibilities = [];

                $can_reconcile = true;

                if ($left_clause->wedge ||
                    $right_clause->wedge ||
                    !$left_clause->reconcilable ||
                    !$right_clause->reconcilable
                ) {
                    $can_reconcile = false;
                }

                foreach ($left_clause->possibilities as $var => $possible_types) {
                    if (isset($right_clause->redefined_vars[$var])) {
                        continue;
                    }

                    if (isset($possibilities[$var])) {
                        $possibilities[$var] = array_merge($possibilities[$var], $possible_types);
                    } else {
                        $possibilities[$var] = $possible_types;
                    }
                }

                foreach ($right_clause->possibilities as $var => $possible_types) {
                    if (isset($possibilities[$var])) {
                        $possibilities[$var] = array_merge($possibilities[$var], $possible_types);
                    } else {
                        $possibilities[$var] = $possible_types;
                    }
                }

                if (count($left_clauses) > 1 || count($right_clauses) > 1) {
                    foreach ($possibilities as $var => $p) {
                        $possibilities[$var] = array_values(array_unique($p));
                    }
                }

                foreach ($possibilities as $var_possibilities) {
                    if (count($var_possibilities) === 2) {
                        if ($var_possibilities[0] === '!' . $var_possibilities[1]
                            || $var_possibilities[1] === '!' . $var_possibilities[0]
                        ) {
                            continue 2;
                        }
                    }
                }

                $creating_conditional_id =
                    $right_clause->creating_conditional_id === $left_clause->creating_conditional_id
                    ? $right_clause->creating_conditional_id
                    : $conditional_object_id;

                $clauses[] = new Clause(
                    $possibilities,
                    $creating_conditional_id,
                    $creating_conditional_id,
                    false,
                    $can_reconcile,
                    $right_clause->generated
                        || $left_clause->generated
                        || count($left_clauses) > 1
                        || count($right_clauses) > 1,
                    []
                );
            }
        }

        if ($has_wedge) {
            $clauses[] = new Clause([], $conditional_object_id, $conditional_object_id, true);
        }

        return $clauses;
    }

    /**
     * Negates a set of clauses
     * negateClauses([$a || $b]) => !$a && !$b
     * negateClauses([$a, $b]) => !$a || !$b
     * negateClauses([$a, $b || $c]) =>
     *   (!$a || !$b) &&
     *   (!$a || !$c)
     * negateClauses([$a, $b || $c, $d || $e || $f]) =>
     *   (!$a || !$b || !$d) &&
     *   (!$a || !$b || !$e) &&
     *   (!$a || !$b || !$f) &&
     *   (!$a || !$c || !$d) &&
     *   (!$a || !$c || !$e) &&
     *   (!$a || !$c || !$f)
     *
     * @param list<Clause>  $clauses
     *
     * @return non-empty-list<Clause>
     */
    public static function negateFormula(array $clauses): array
    {
        if (!$clauses) {
            $cond_id = \mt_rand(0, 100000000);
            return [new Clause([], $cond_id, $cond_id, true)];
        }

        $clauses_with_impossibilities = [];

        foreach ($clauses as $clause) {
            $clauses_with_impossibilities[] = $clause->calculateNegation();
        }

        unset($clauses);

        $impossible_clauses = self::groupImpossibilities($clauses_with_impossibilities);

        if (!$impossible_clauses) {
            $cond_id = \mt_rand(0, 100000000);
            return [new Clause([], $cond_id, $cond_id, true)];
        }

        $negated = self::simplifyCNF($impossible_clauses);

        if (!$negated) {
            $cond_id = \mt_rand(0, 100000000);
            return [new Clause([], $cond_id, $cond_id, true)];
        }

        return $negated;
    }
}
