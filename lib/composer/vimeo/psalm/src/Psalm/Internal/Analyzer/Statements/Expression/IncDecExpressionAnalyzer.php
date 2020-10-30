<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use PhpParser\Node\Expr\PostInc;
use PhpParser\Node\Expr\PostDec;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Expr\PreDec;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Context;
use Psalm\Type;

class IncDecExpressionAnalyzer
{
    /**
     * @param PostInc|PostDec|PreInc|PreDec $stmt
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context
    ) : bool {
        $was_inside_assignment = $context->inside_assignment;
        $context->inside_assignment = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->var, $context) === false) {
            if (!$was_inside_assignment) {
                $context->inside_assignment = false;
            }
            return false;
        }

        if (!$was_inside_assignment) {
            $context->inside_assignment = false;
        }

        $stmt_var_type = $statements_analyzer->node_data->getType($stmt->var);

        if ($stmt instanceof PostInc || $stmt instanceof PostDec) {
            $statements_analyzer->node_data->setType($stmt, $stmt_var_type ?: Type::getMixed());
        }

        if (($stmt_var_type = $statements_analyzer->node_data->getType($stmt->var))
            && $stmt_var_type->hasString()
            && ($stmt instanceof PostInc || $stmt instanceof PreInc)
        ) {
            $return_type = null;

            $fake_right_expr = new PhpParser\Node\Scalar\LNumber(1, $stmt->getAttributes());
            $statements_analyzer->node_data->setType($fake_right_expr, Type::getInt());

            BinaryOp\NonDivArithmeticOpAnalyzer::analyze(
                $statements_analyzer,
                $statements_analyzer->node_data,
                $stmt->var,
                $fake_right_expr,
                $stmt,
                $return_type,
                $context
            );

            $stmt_type = clone $stmt_var_type;

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            BinaryOpAnalyzer::addDataFlow(
                $statements_analyzer,
                $stmt,
                $stmt->var,
                $fake_right_expr,
                'inc'
            );

            $var_id = ExpressionIdentifier::getArrayVarId($stmt->var, null);

            $codebase = $statements_analyzer->getCodebase();

            if ($var_id && isset($context->vars_in_scope[$var_id])) {
                $context->vars_in_scope[$var_id] = $stmt_type;

                if ($codebase->find_unused_variables && $stmt->var instanceof PhpParser\Node\Expr\Variable) {
                    $context->assigned_var_ids[$var_id] = true;
                    $context->possibly_assigned_var_ids[$var_id] = true;
                }

                // removes dependent vars from $context
                $context->removeDescendents(
                    $var_id,
                    $context->vars_in_scope[$var_id],
                    $return_type,
                    $statements_analyzer
                );
            }
        } else {
            $fake_right_expr = new PhpParser\Node\Scalar\LNumber(1, $stmt->getAttributes());

            $operation = $stmt instanceof PostInc || $stmt instanceof PreInc
                ? new PhpParser\Node\Expr\BinaryOp\Plus(
                    $stmt->var,
                    $fake_right_expr,
                    $stmt->var->getAttributes()
                )
                : new PhpParser\Node\Expr\BinaryOp\Minus(
                    $stmt->var,
                    $fake_right_expr,
                    $stmt->var->getAttributes()
                );

            $fake_assignment = new PhpParser\Node\Expr\Assign(
                $stmt->var,
                $operation,
                $stmt->getAttributes()
            );

            $old_node_data = $statements_analyzer->node_data;

            $statements_analyzer->node_data = clone $statements_analyzer->node_data;

            if (ExpressionAnalyzer::analyze($statements_analyzer, $fake_assignment, $context) === false) {
                return false;
            }

            if ($stmt instanceof PreInc || $stmt instanceof PreDec) {
                $old_node_data->setType(
                    $stmt,
                    $statements_analyzer->node_data->getType($operation) ?: Type::getMixed()
                );
            }

            $statements_analyzer->node_data = $old_node_data;
        }

        return true;
    }
}
