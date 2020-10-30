<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Context;
use Psalm\Type;

class BooleanNotAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BooleanNot $stmt,
        Context $context
    ) : bool {
        $stmt_type = Type::getBool();
        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        $inside_negation = $context->inside_negation;

        $context->inside_negation = !$inside_negation;

        $result = ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context);

        $context->inside_negation = $inside_negation;

        $expr_type = $statements_analyzer->node_data->getType($stmt->expr);

        if ($expr_type) {
            $stmt_type->parent_nodes = $expr_type->parent_nodes;
        }

        return $result;
    }
}
