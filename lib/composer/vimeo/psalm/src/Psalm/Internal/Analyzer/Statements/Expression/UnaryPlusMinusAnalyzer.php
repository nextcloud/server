<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Context;
use Psalm\Type;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TString;

class UnaryPlusMinusAnalyzer
{
    /**
     * @param PhpParser\Node\Expr\UnaryMinus|PhpParser\Node\Expr\UnaryPlus $stmt
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context
    ) : bool {
        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        if (!($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr))) {
            $statements_analyzer->node_data->setType($stmt, new Type\Union([new TInt, new TFloat]));
        } elseif ($stmt_expr_type->isMixed()) {
            $statements_analyzer->node_data->setType($stmt, Type::getMixed());
        } else {
            $acceptable_types = [];

            foreach ($stmt_expr_type->getAtomicTypes() as $type_part) {
                if ($type_part instanceof TInt || $type_part instanceof TFloat) {
                    if ($type_part instanceof Type\Atomic\TLiteralInt
                        && $stmt instanceof PhpParser\Node\Expr\UnaryMinus
                    ) {
                        $type_part->value = -$type_part->value;
                    } elseif ($type_part instanceof Type\Atomic\TLiteralFloat
                        && $stmt instanceof PhpParser\Node\Expr\UnaryMinus
                    ) {
                        $type_part->value = -$type_part->value;
                    }

                    $acceptable_types[] = $type_part;
                } elseif ($type_part instanceof TString) {
                    $acceptable_types[] = new TInt;
                    $acceptable_types[] = new TFloat;
                } else {
                    $acceptable_types[] = new TInt;
                }
            }

            $statements_analyzer->node_data->setType($stmt, new Type\Union($acceptable_types));
        }

        return true;
    }
}
