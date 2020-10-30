<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\TaintSink;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TString;

class ExitAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Exit_ $stmt,
        Context $context
    ) : bool {
        if ($stmt->expr) {
            $context->inside_call = true;

            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph) {
                $call_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

                $echo_param_sink = TaintSink::getForMethodArgument(
                    'exit',
                    'exit',
                    0,
                    null,
                    $call_location
                );

                $echo_param_sink->taints = [
                    Type\TaintKind::INPUT_HTML,
                    Type\TaintKind::USER_SECRET,
                    Type\TaintKind::SYSTEM_SECRET
                ];

                $statements_analyzer->data_flow_graph->addSink($echo_param_sink);
            }

            if ($expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
                $exit_param = new FunctionLikeParameter(
                    'var',
                    false
                );

                if (ArgumentAnalyzer::verifyType(
                    $statements_analyzer,
                    $expr_type,
                    new Type\Union([new TInt(), new TString()]),
                    null,
                    'exit',
                    0,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->expr),
                    $stmt->expr,
                    $context,
                    $exit_param,
                    false,
                    null,
                    true,
                    true,
                    new CodeLocation($statements_analyzer, $stmt)
                ) === false) {
                    return false;
                }
            }

            $context->inside_call = false;
        }

        return true;
    }
}
