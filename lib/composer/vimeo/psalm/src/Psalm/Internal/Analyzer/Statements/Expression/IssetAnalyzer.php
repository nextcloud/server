<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Context;
use Psalm\Type;

class IssetAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Isset_ $stmt,
        Context $context
    ): void {
        foreach ($stmt->vars as $isset_var) {
            if ($isset_var instanceof PhpParser\Node\Expr\PropertyFetch
                && $isset_var->var instanceof PhpParser\Node\Expr\Variable
                && $isset_var->var->name === 'this'
                && $isset_var->name instanceof PhpParser\Node\Identifier
            ) {
                $var_id = '$this->' . $isset_var->name->name;

                if (!isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                    $context->vars_possibly_in_scope[$var_id] = true;
                }
            }

            self::analyzeIssetVar($statements_analyzer, $isset_var, $context);
        }

        $statements_analyzer->node_data->setType($stmt, Type::getBool());
    }

    public static function analyzeIssetVar(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context
    ) : bool {

        $context->inside_isset = true;

        $result = ExpressionAnalyzer::analyze($statements_analyzer, $stmt, $context);

        $context->inside_isset = false;

        return $result;
    }
}
