<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Context;
use Psalm\Type;
use function strtolower;

class YieldFromAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\YieldFrom $stmt,
        Context $context
    ) : bool {
        $was_inside_call = $context->inside_call;

        $context->inside_call = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            $context->inside_call = $was_inside_call;

            return false;
        }

        if ($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
            $yield_from_type = null;

            foreach ($stmt_expr_type->getAtomicTypes() as $atomic_type) {
                if ($yield_from_type === null) {
                    if ($atomic_type instanceof Type\Atomic\TGenericObject
                        && strtolower($atomic_type->value) === 'generator'
                        && isset($atomic_type->type_params[3])
                    ) {
                        $yield_from_type = clone $atomic_type->type_params[3];
                    } elseif ($atomic_type instanceof Type\Atomic\TArray) {
                        $yield_from_type = clone $atomic_type->type_params[1];
                    } elseif ($atomic_type instanceof Type\Atomic\TKeyedArray) {
                        $yield_from_type = $atomic_type->getGenericValueType();
                    }
                } else {
                    $yield_from_type = Type::getMixed();
                }
            }

            // this should be whatever the generator above returns, but *not* the return type
            $statements_analyzer->node_data->setType($stmt, $yield_from_type ?: Type::getMixed());
        }

        $context->inside_call = $was_inside_call;

        return true;
    }
}
