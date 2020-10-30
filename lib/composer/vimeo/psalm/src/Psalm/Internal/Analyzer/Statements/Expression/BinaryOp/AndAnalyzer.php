<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type\Algebra;
use Psalm\Type\Reconciler;
use function array_merge;
use function array_diff_key;
use function array_filter;
use function array_values;
use function array_map;

/**
 * @internal
 */
class AndAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BinaryOp $stmt,
        Context $context,
        bool $from_stmt = false
    ) : bool {
        if ($from_stmt) {
            $fake_if_stmt = new PhpParser\Node\Stmt\If_(
                $stmt->left,
                [
                    'stmts' => [
                        new PhpParser\Node\Stmt\Expression(
                            $stmt->right
                        )
                    ]
                ],
                $stmt->getAttributes()
            );

            return IfAnalyzer::analyze($statements_analyzer, $fake_if_stmt, $context) !== false;
        }

        $pre_referenced_var_ids = $context->referenced_var_ids;

        $pre_assigned_var_ids = $context->assigned_var_ids;

        $left_context = clone $context;

        $left_context->referenced_var_ids = [];
        $left_context->assigned_var_ids = [];

        /** @var list<string> */
        $left_context->reconciled_expression_clauses = [];

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->left, $left_context) === false) {
            return false;
        }

        $codebase = $statements_analyzer->getCodebase();

        $left_cond_id = \spl_object_id($stmt->left);

        $left_clauses = Algebra::getFormula(
            $left_cond_id,
            $left_cond_id,
            $stmt->left,
            $context->self,
            $statements_analyzer,
            $codebase
        );

        foreach ($left_context->vars_in_scope as $var_id => $type) {
            if (isset($left_context->assigned_var_ids[$var_id])) {
                $context->vars_in_scope[$var_id] = $type;
            }
        }

        /** @var array<string, bool> */
        $left_referenced_var_ids = $left_context->referenced_var_ids;
        $context->referenced_var_ids = array_merge($pre_referenced_var_ids, $left_referenced_var_ids);

        $left_assigned_var_ids = array_diff_key($left_context->assigned_var_ids, $pre_assigned_var_ids);

        $left_referenced_var_ids = array_diff_key($left_referenced_var_ids, $left_assigned_var_ids);

        $context_clauses = array_merge($left_context->clauses, $left_clauses);

        if ($left_context->reconciled_expression_clauses) {
            $reconciled_expression_clauses = $left_context->reconciled_expression_clauses;

            $context_clauses = array_values(
                array_filter(
                    $context_clauses,
                    function ($c) use ($reconciled_expression_clauses): bool {
                        return !\in_array($c->hash, $reconciled_expression_clauses);
                    }
                )
            );

            if (\count($context_clauses) === 1
                && $context_clauses[0]->wedge
                && !$context_clauses[0]->possibilities
            ) {
                $context_clauses = [];
            }
        }

        $simplified_clauses = Algebra::simplifyCNF($context_clauses);

        $active_left_assertions = [];

        $left_type_assertions = Algebra::getTruthsFromFormula(
            $simplified_clauses,
            $left_cond_id,
            $left_referenced_var_ids,
            $active_left_assertions
        );

        $changed_var_ids = [];

        $right_context = clone $left_context;

        if ($left_type_assertions) {
            // while in an and, we allow scope to boil over to support
            // statements of the form if ($x && $x->foo())
            $right_vars_in_scope = Reconciler::reconcileKeyedTypes(
                $left_type_assertions,
                $active_left_assertions,
                $context->vars_in_scope,
                $changed_var_ids,
                $left_referenced_var_ids,
                $statements_analyzer,
                [],
                $context->inside_loop,
                new CodeLocation($statements_analyzer->getSource(), $stmt->left),
                $context->inside_negation
            );

            $right_context->vars_in_scope = $right_vars_in_scope;

            if ($context->if_scope) {
                $context->if_scope->if_cond_changed_var_ids += $changed_var_ids;
            }
        }

        $partitioned_clauses = Context::removeReconciledClauses($left_clauses, $changed_var_ids);

        $right_context->clauses = $partitioned_clauses[0];

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->right, $right_context) === false) {
            return false;
        }

        $context->referenced_var_ids = array_merge(
            $right_context->referenced_var_ids,
            $left_context->referenced_var_ids
        );

        if ($context->inside_conditional) {
            $context->updateChecks($right_context);

            $context->vars_possibly_in_scope = array_merge(
                $right_context->vars_possibly_in_scope,
                $left_context->vars_possibly_in_scope
            );

            $context->assigned_var_ids = array_merge(
                $left_context->assigned_var_ids,
                $right_context->assigned_var_ids
            );
        }

        if ($context->if_context && !$context->inside_negation) {
            $context->vars_in_scope = $right_context->vars_in_scope;
            $if_context = $context->if_context;

            foreach ($right_context->vars_in_scope as $var_id => $type) {
                if (!isset($if_context->vars_in_scope[$var_id])) {
                    $if_context->vars_in_scope[$var_id] = $type;
                } elseif (isset($context->vars_in_scope[$var_id])) {
                    $if_context->vars_in_scope[$var_id] = $context->vars_in_scope[$var_id];
                }
            }

            $if_context->referenced_var_ids = array_merge(
                $context->referenced_var_ids,
                $if_context->referenced_var_ids
            );

            $if_context->assigned_var_ids = array_merge(
                $context->assigned_var_ids,
                $if_context->assigned_var_ids
            );

            $if_context->reconciled_expression_clauses = array_merge(
                $if_context->reconciled_expression_clauses,
                array_map(
                    function ($c) {
                        return $c->hash;
                    },
                    $partitioned_clauses[1]
                )
            );

            $if_context->vars_possibly_in_scope = array_merge(
                $context->vars_possibly_in_scope,
                $if_context->vars_possibly_in_scope
            );

            $if_context->updateChecks($context);
        } else {
            $context->vars_in_scope = $left_context->vars_in_scope;
        }

        return true;
    }
}
