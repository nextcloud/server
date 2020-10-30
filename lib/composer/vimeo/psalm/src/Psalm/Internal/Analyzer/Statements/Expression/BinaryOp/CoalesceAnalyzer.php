<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Algebra;
use Psalm\Type\Reconciler;
use Psalm\Internal\Type\AssertionReconciler;
use function array_merge;
use function array_values;
use function array_map;
use function array_keys;
use function preg_match;
use function preg_quote;

/**
 * @internal
 */
class CoalesceAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BinaryOp $stmt,
        Context $context
    ) : bool {
        $t_if_context = clone $context;

        $codebase = $statements_analyzer->getCodebase();

        $stmt_id = \spl_object_id($stmt);

        $if_clauses = Algebra::getFormula(
            $stmt_id,
            $stmt_id,
            $stmt,
            $context->self,
            $statements_analyzer,
            $codebase
        );

        $mixed_var_ids = [];

        foreach ($context->vars_in_scope as $var_id => $type) {
            if ($type->hasMixed()) {
                $mixed_var_ids[] = $var_id;
            }
        }

        foreach ($context->vars_possibly_in_scope as $var_id => $_) {
            if (!isset($context->vars_in_scope[$var_id])) {
                $mixed_var_ids[] = $var_id;
            }
        }

        $if_clauses = array_values(
            array_map(
                function (\Psalm\Internal\Clause $c) use ($mixed_var_ids, $stmt_id): \Psalm\Internal\Clause {
                    $keys = array_keys($c->possibilities);

                    $mixed_var_ids = \array_diff($mixed_var_ids, $keys);

                    foreach ($keys as $key) {
                        foreach ($mixed_var_ids as $mixed_var_id) {
                            if (preg_match('/^' . preg_quote($mixed_var_id, '/') . '(\[|-)/', $key)) {
                                return new \Psalm\Internal\Clause([], $stmt_id, $stmt_id, true);
                            }
                        }
                    }

                    return $c;
                },
                $if_clauses
            )
        );

        $ternary_clauses = Algebra::simplifyCNF(array_merge($context->clauses, $if_clauses));

        $negated_clauses = Algebra::negateFormula($if_clauses);

        $negated_if_types = Algebra::getTruthsFromFormula($negated_clauses);

        $reconcilable_if_types = Algebra::getTruthsFromFormula($ternary_clauses);

        $changed_var_ids = [];

        if ($reconcilable_if_types) {
            $t_if_vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
                $reconcilable_if_types,
                [],
                $t_if_context->vars_in_scope,
                $changed_var_ids,
                [],
                $statements_analyzer,
                [],
                $t_if_context->inside_loop,
                new CodeLocation($statements_analyzer->getSource(), $stmt->left)
            );

            foreach ($context->vars_in_scope as $var_id => $_) {
                if (isset($t_if_vars_in_scope_reconciled[$var_id])) {
                    $t_if_context->vars_in_scope[$var_id] = $t_if_vars_in_scope_reconciled[$var_id];
                }
            }
        }

        if (!self::hasArrayDimFetch($stmt->left)) {
            // check first if the variable was good

            IssueBuffer::startRecording();

            ExpressionAnalyzer::analyze($statements_analyzer, $stmt->left, clone $context);

            IssueBuffer::clearRecordingLevel();
            IssueBuffer::stopRecording();

            $naive_type = $statements_analyzer->node_data->getType($stmt->left);

            if ($naive_type
                && !$naive_type->possibly_undefined
                && !$naive_type->hasMixed()
                && !$naive_type->isNullable()
            ) {
                $var_id = ExpressionIdentifier::getVarId($stmt->left, $context->self);

                if (!$var_id
                    || ($var_id !== '$_SESSION' && $var_id !== '$_SERVER' && !isset($changed_var_ids[$var_id]))
                ) {
                    if ($naive_type->from_docblock) {
                        if (IssueBuffer::accepts(
                            new \Psalm\Issue\DocblockTypeContradiction(
                                $naive_type->getId() . ' does not contain null',
                                new CodeLocation($statements_analyzer, $stmt->left),
                                $naive_type->getId() . ' null'
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new \Psalm\Issue\TypeDoesNotContainType(
                                $naive_type->getId() . ' is always defined and non-null',
                                new CodeLocation($statements_analyzer, $stmt->left),
                                null
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }
            }
        }

        $t_if_context->inside_isset = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->left, $t_if_context) === false) {
            return false;
        }

        $t_if_context->inside_isset = false;

        foreach ($t_if_context->vars_in_scope as $var_id => $type) {
            if (isset($context->vars_in_scope[$var_id])) {
                $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                    $context->vars_in_scope[$var_id],
                    $type,
                    $codebase
                );
            } else {
                $context->vars_in_scope[$var_id] = $type;
            }
        }

        $context->referenced_var_ids = array_merge(
            $context->referenced_var_ids,
            $t_if_context->referenced_var_ids
        );

        $t_else_context = clone $context;

        if ($negated_if_types) {
            $t_else_vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
                $negated_if_types,
                [],
                $t_else_context->vars_in_scope,
                $changed_var_ids,
                [],
                $statements_analyzer,
                [],
                $t_else_context->inside_loop,
                new CodeLocation($statements_analyzer->getSource(), $stmt->right)
            );

            $t_else_context->vars_in_scope = $t_else_vars_in_scope_reconciled;
        }

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->right, $t_else_context) === false) {
            return false;
        }

        $context->referenced_var_ids = array_merge(
            $context->referenced_var_ids,
            $t_else_context->referenced_var_ids
        );

        $lhs_type = null;

        $stmt_left_type = $statements_analyzer->node_data->getType($stmt->left);

        if ($stmt_left_type) {
            $if_return_type_reconciled = AssertionReconciler::reconcile(
                'isset',
                clone $stmt_left_type,
                '',
                $statements_analyzer,
                $context->inside_loop,
                [],
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer->getSuppressedIssues()
            );

            $lhs_type = clone $if_return_type_reconciled;
        }

        $stmt_right_type = null;

        if (!$lhs_type || !($stmt_right_type = $statements_analyzer->node_data->getType($stmt->right))) {
            $stmt_type = Type::getMixed();

            $statements_analyzer->node_data->setType($stmt, $stmt_type);
        } else {
            $stmt_type = Type::combineUnionTypes(
                $lhs_type,
                $stmt_right_type,
                $codebase
            );

            $statements_analyzer->node_data->setType($stmt, $stmt_type);
        }

        return true;
    }

    private static function hasArrayDimFetch(PhpParser\Node\Expr $expr) : bool
    {
        if ($expr instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            return true;
        }

        if ($expr instanceof PhpParser\Node\Expr\PropertyFetch
            || $expr instanceof PhpParser\Node\Expr\MethodCall
        ) {
            return self::hasArrayDimFetch($expr->var);
        }

        return false;
    }
}
