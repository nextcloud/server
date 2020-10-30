<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\TaintSink;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ForbiddenCode;
use Psalm\IssueBuffer;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;

class PrintAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Print_ $stmt,
        Context $context
    ) : bool {
        $codebase = $statements_analyzer->getCodebase();

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph) {
            $call_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

            $print_param_sink = TaintSink::getForMethodArgument(
                'print',
                'print',
                0,
                null,
                $call_location
            );

            $print_param_sink->taints = [
                Type\TaintKind::INPUT_HTML,
                Type\TaintKind::USER_SECRET,
                Type\TaintKind::SYSTEM_SECRET
            ];

            $statements_analyzer->data_flow_graph->addSink($print_param_sink);
        }

        if ($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
            if (Call\ArgumentAnalyzer::verifyType(
                $statements_analyzer,
                $stmt_expr_type,
                Type::getString(),
                null,
                'print',
                0,
                new CodeLocation($statements_analyzer->getSource(), $stmt->expr),
                $stmt->expr,
                $context,
                new FunctionLikeParameter('var', false),
                false,
                null,
                true,
                true,
                new CodeLocation($statements_analyzer->getSource(), $stmt)
            ) === false) {
                return false;
            }
        }

        if (isset($codebase->config->forbidden_functions['print'])) {
            if (IssueBuffer::accepts(
                new ForbiddenCode(
                    'You have forbidden the use of print',
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // continue
            }
        }

        $statements_analyzer->node_data->setType($stmt, Type::getInt(false, 1));

        return true;
    }
}
