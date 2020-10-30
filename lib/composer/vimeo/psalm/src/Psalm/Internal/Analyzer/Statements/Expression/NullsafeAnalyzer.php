<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Context;
use Psalm\Type;

/**
 * @internal
 */
class NullsafeAnalyzer
{
    /**
     * @param PhpParser\Node\Expr\NullsafePropertyFetch|PhpParser\Node\Expr\NullsafeMethodCall $stmt
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context
    ) : bool {
        if (!$stmt->var instanceof PhpParser\Node\Expr\Variable) {
            ExpressionAnalyzer::analyze($statements_analyzer, $stmt->var, $context);

            $tmp_name = '__tmp_nullsafe__' . (int) $stmt->var->getAttribute('startFilePos');

            $condition_type = $statements_analyzer->node_data->getType($stmt->var);

            if ($condition_type) {
                $context->vars_in_scope['$' . $tmp_name] = $condition_type;

                $tmp_var = new PhpParser\Node\Expr\Variable($tmp_name, $stmt->var->getAttributes());
            } else {
                $tmp_var = $stmt->var;
            }
        } else {
            $tmp_var = $stmt->var;
        }

        $old_node_data = $statements_analyzer->node_data;
        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        $null_value1 = new PhpParser\Node\Expr\ConstFetch(
            new PhpParser\Node\Name('null'),
            $stmt->var->getAttributes()
        );

        $null_comparison = new PhpParser\Node\Expr\BinaryOp\Identical(
            $tmp_var,
            $null_value1,
            $stmt->var->getAttributes()
        );

        $null_value2 = new PhpParser\Node\Expr\ConstFetch(
            new PhpParser\Node\Name('null'),
            $stmt->var->getAttributes()
        );

        if ($stmt instanceof PhpParser\Node\Expr\NullsafePropertyFetch) {
            $ternary = new PhpParser\Node\Expr\Ternary(
                $null_comparison,
                $null_value2,
                new PhpParser\Node\Expr\PropertyFetch($tmp_var, $stmt->name, $stmt->getAttributes()),
                $stmt->getAttributes()
            );
        } else {
            $ternary = new PhpParser\Node\Expr\Ternary(
                $null_comparison,
                $null_value2,
                new PhpParser\Node\Expr\MethodCall($tmp_var, $stmt->name, $stmt->args, $stmt->getAttributes()),
                $stmt->getAttributes()
            );
        }

        ExpressionAnalyzer::analyze($statements_analyzer, $ternary, $context);

        $ternary_type = $statements_analyzer->node_data->getType($ternary);

        $statements_analyzer->node_data = $old_node_data;

        $statements_analyzer->node_data->setType($stmt, $ternary_type ?: Type::getMixed());

        return true;
    }
}
