<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Issue\UnhandledMatchCondition;
use Psalm\Context;
use Psalm\Type;

use function substr;
use function array_reverse;
use function array_shift;
use function in_array;
use function count;
use function array_map;

class MatchAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Match_ $stmt,
        Context $context
    ) : bool {
        $was_inside_call = $context->inside_call;

        $context->inside_call = true;

        $was_inside_conditional = $context->inside_conditional;

        $context->inside_conditional = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->cond, $context) === false) {
            $context->inside_conditional = $was_inside_conditional;

            return false;
        }

        $context->inside_conditional = $was_inside_conditional;

        $switch_var_id = ExpressionIdentifier::getArrayVarId(
            $stmt->cond,
            null,
            $statements_analyzer
        );

        $match_condition = $stmt->cond;

        if (!$switch_var_id
            && ($stmt->cond instanceof PhpParser\Node\Expr\FuncCall
                || $stmt->cond instanceof PhpParser\Node\Expr\MethodCall
                || $stmt->cond instanceof PhpParser\Node\Expr\StaticCall
            )
        ) {
            $switch_var_id = '$__tmp_switch__' . (int) $stmt->cond->getAttribute('startFilePos');

            $condition_type = $statements_analyzer->node_data->getType($stmt->cond) ?: Type::getMixed();

            $context->vars_in_scope[$switch_var_id] = $condition_type;

            $match_condition = new PhpParser\Node\Expr\Variable(
                substr($switch_var_id, 1),
                $stmt->cond->getAttributes()
            );
        }

        $arms = $stmt->arms;

        foreach ($arms as $i => $arm) {
            // move default to the end
            if ($arm->conds === null) {
                unset($arms[$i]);
                $arms[] = $arm;
            }
        }

        $arms = array_reverse($arms);

        $last_arm = array_shift($arms);

        $old_node_data = $statements_analyzer->node_data;

        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        if (!$last_arm->conds) {
            $ternary = $last_arm->body;
        } else {
            $ternary = new PhpParser\Node\Expr\Ternary(
                self::convertCondsToConditional($last_arm->conds, $match_condition, $last_arm->getAttributes()),
                $last_arm->body,
                new PhpParser\Node\Expr\Throw_(
                    new PhpParser\Node\Expr\New_(
                        new PhpParser\Node\Name\FullyQualified(
                            'UnhandledMatchError'
                        )
                    )
                )
            );
        }

        foreach ($arms as $arm) {
            if (!$arm->conds) {
                continue;
            }

            $ternary = new PhpParser\Node\Expr\Ternary(
                self::convertCondsToConditional($arm->conds, $match_condition, $arm->getAttributes()),
                $arm->body,
                $ternary,
                $arm->getAttributes()
            );
        }

        $suppressed_issues = $statements_analyzer->getSuppressedIssues();

        if (!in_array('RedundantCondition', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['RedundantCondition']);
        }

        if (!in_array('RedundantConditionGivenDocblockType', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['RedundantConditionGivenDocblockType']);
        }

        if (ExpressionAnalyzer::analyze($statements_analyzer, $ternary, $context) === false) {
            return false;
        }

        if (!in_array('RedundantCondition', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['RedundantCondition']);
        }

        if (!in_array('RedundantConditionGivenDocblockType', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['RedundantConditionGivenDocblockType']);
        }

        if ($switch_var_id && $last_arm->conds) {
            $codebase = $statements_analyzer->getCodebase();

            $all_conds = $last_arm->conds;

            foreach ($arms as $arm) {
                if (!$arm->conds) {
                    throw new \UnexpectedValueException('bad');
                }

                $all_conds = \array_merge($arm->conds, $all_conds);
            }

            $all_match_condition = self::convertCondsToConditional(
                \array_values($all_conds),
                $match_condition,
                $match_condition->getAttributes()
            );

            ExpressionAnalyzer::analyze($statements_analyzer, $all_match_condition, $context);

            $clauses = \Psalm\Type\Algebra::getFormula(
                \spl_object_id($all_match_condition),
                \spl_object_id($all_match_condition),
                $all_match_condition,
                $context->self,
                $statements_analyzer,
                $codebase,
                false,
                false
            );

            $reconcilable_types = \Psalm\Type\Algebra::getTruthsFromFormula(
                \Psalm\Type\Algebra::negateFormula($clauses)
            );

            // if the if has an || in the conditional, we cannot easily reason about it
            if ($reconcilable_types) {
                $changed_var_ids = [];

                $vars_in_scope_reconciled = \Psalm\Type\Reconciler::reconcileKeyedTypes(
                    $reconcilable_types,
                    [],
                    $context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_analyzer,
                    [],
                    $context->inside_loop,
                    null
                );

                if (isset($vars_in_scope_reconciled[$switch_var_id])) {
                    if ($vars_in_scope_reconciled[$switch_var_id]->hasLiteralValue()) {
                        if (\Psalm\IssueBuffer::accepts(
                            new UnhandledMatchCondition(
                                'This match expression is not exhaustive - consider values '
                                    . $vars_in_scope_reconciled[$switch_var_id]->getId(),
                                new \Psalm\CodeLocation($statements_analyzer->getSource(), $match_condition)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // continue
                        }
                    }
                }
            }
        }

        $stmt_expr_type = $statements_analyzer->node_data->getType($ternary);

        $old_node_data->setType($stmt, $stmt_expr_type ?: Type::getMixed());

        $statements_analyzer->node_data = $old_node_data;

        $context->inside_call = $was_inside_call;

        return true;
    }

    /**
     * @param non-empty-list<PhpParser\Node\Expr> $conds
     */
    private static function convertCondsToConditional(
        array $conds,
        PhpParser\Node\Expr $match_condition,
        array $attributes
    ) : PhpParser\Node\Expr {
        if (count($conds) === 1) {
            return new PhpParser\Node\Expr\BinaryOp\Identical(
                $match_condition,
                $conds[0],
                $attributes
            );
        }

        $array_items = array_map(
            function ($cond): PhpParser\Node\Expr\ArrayItem {
                return new PhpParser\Node\Expr\ArrayItem($cond, null, false, $cond->getAttributes());
            },
            $conds
        );

        return new PhpParser\Node\Expr\FuncCall(
            new PhpParser\Node\Name\FullyQualified(['in_array']),
            [
                new PhpParser\Node\Arg(
                    $match_condition
                ),
                new PhpParser\Node\Arg(
                    new PhpParser\Node\Expr\Array_(
                        $array_items
                    )
                ),
                new PhpParser\Node\Arg(
                    new PhpParser\Node\Expr\ConstFetch(
                        new PhpParser\Node\Name\FullyQualified(['true'])
                    )
                ),
            ],
            $attributes
        );
    }
}
