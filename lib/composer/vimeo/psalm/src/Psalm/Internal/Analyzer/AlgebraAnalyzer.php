<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Internal\Clause;
use Psalm\CodeLocation;
use Psalm\Issue\ParadoxicalCondition;
use Psalm\Issue\RedundantCondition;
use Psalm\IssueBuffer;
use Psalm\Type\Algebra;
use function array_intersect_key;
use function count;
use function array_unique;

/**
 * @internal
 */
class AlgebraAnalyzer
{
    /**
     * This looks to see if there are any clauses in one formula that contradict
     * clauses in another formula, or clauses that duplicate previous clauses
     *
     * e.g.
     * if ($a) { }
     * elseif ($a) { }
     *
     * @param  list<Clause>   $formula_1
     * @param  list<Clause>   $formula_2
     * @param  array<string, bool>  $new_assigned_var_ids
     */
    public static function checkForParadox(
        array $formula_1,
        array $formula_2,
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node $stmt,
        array $new_assigned_var_ids
    ): void {
        try {
            $negated_formula2 = Algebra::negateFormula($formula_2);
        } catch (\Psalm\Exception\ComplicatedExpressionException $e) {
            return;
        }

        $formula_1_hashes = [];

        foreach ($formula_1 as $formula_1_clause) {
            $formula_1_hashes[$formula_1_clause->hash] = true;
        }

        $formula_2_hashes = [];

        foreach ($formula_2 as $formula_2_clause) {
            $hash = $formula_2_clause->hash;

            if (!$formula_2_clause->generated
                && !$formula_2_clause->wedge
                && $formula_2_clause->reconcilable
                && (isset($formula_1_hashes[$hash]) || isset($formula_2_hashes[$hash]))
                && !array_intersect_key($new_assigned_var_ids, $formula_2_clause->possibilities)
            ) {
                if (IssueBuffer::accepts(
                    new RedundantCondition(
                        $formula_2_clause . ' has already been asserted',
                        new CodeLocation($statements_analyzer, $stmt),
                        null
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            foreach ($formula_2_clause->possibilities as $key => $values) {
                if (!$formula_2_clause->generated
                    && count($values) > 1
                    && !isset($new_assigned_var_ids[$key])
                    && count(array_unique($values)) < count($values)
                ) {
                    if (IssueBuffer::accepts(
                        new ParadoxicalCondition(
                            'Found a redundant condition when evaluating assertion (' . $formula_2_clause . ')',
                            new CodeLocation($statements_analyzer, $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            $formula_2_hashes[$hash] = true;
        }

        // remove impossible types
        foreach ($negated_formula2 as $negated_clause_2) {
            if (count($negated_formula2) === 1) {
                foreach ($negated_clause_2->possibilities as $key => $values) {
                    if (count($values) > 1
                        && !isset($new_assigned_var_ids[$key])
                        && count(array_unique($values)) < count($values)
                    ) {
                        if (IssueBuffer::accepts(
                            new RedundantCondition(
                                'Found a redundant condition when evaluating ' . $key,
                                new CodeLocation($statements_analyzer, $stmt),
                                null
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }
            }

            if (!$negated_clause_2->reconcilable || $negated_clause_2->wedge) {
                continue;
            }

            foreach ($formula_1 as $clause_1) {
                if ($negated_clause_2 === $clause_1 || !$clause_1->reconcilable || $clause_1->wedge) {
                    continue;
                }

                $negated_clause_2_contains_1_possibilities = true;

                foreach ($clause_1->possibilities as $key => $keyed_possibilities) {
                    if (!isset($negated_clause_2->possibilities[$key])) {
                        $negated_clause_2_contains_1_possibilities = false;
                        break;
                    }

                    if ($negated_clause_2->possibilities[$key] != $keyed_possibilities) {
                        $negated_clause_2_contains_1_possibilities = false;
                        break;
                    }
                }

                if ($negated_clause_2_contains_1_possibilities) {
                    $mini_formula_2 = Algebra::negateFormula([$negated_clause_2]);

                    if (!$mini_formula_2[0]->wedge) {
                        if (count($mini_formula_2) > 1) {
                            $paradox_message = 'Condition ((' . \implode(') && (', $mini_formula_2) . '))'
                                . ' contradicts a previously-established condition (' . $clause_1 . ')';
                        } else {
                            $paradox_message = 'Condition (' . $mini_formula_2[0] . ')'
                                . ' contradicts a previously-established condition (' . $clause_1 . ')';
                        }
                    } else {
                        $paradox_message = 'Condition not(' . $negated_clause_2 . ')'
                            . ' contradicts a previously-established condition (' . $clause_1 . ')';
                    }

                    if (IssueBuffer::accepts(
                        new ParadoxicalCondition(
                            $paradox_message,
                            new CodeLocation($statements_analyzer, $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    return;
                }
            }
        }
    }
}
