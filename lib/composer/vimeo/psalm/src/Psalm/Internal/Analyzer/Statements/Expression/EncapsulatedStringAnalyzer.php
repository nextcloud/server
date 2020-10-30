<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;

class EncapsulatedStringAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Scalar\Encapsed $stmt,
        Context $context
    ) : bool {
        $stmt_type = Type::getString();

        foreach ($stmt->parts as $part) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $part, $context) === false) {
                return false;
            }

            $part_type = $statements_analyzer->node_data->getType($part);

            if ($part_type) {
                $casted_part_type = CastAnalyzer::castStringAttempt(
                    $statements_analyzer,
                    $context,
                    $part_type,
                    $part
                );

                if ($statements_analyzer->data_flow_graph
                    && !\in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
                ) {
                    $var_location = new CodeLocation($statements_analyzer, $part);

                    $new_parent_node = DataFlowNode::getForAssignment('concat', $var_location);
                    $statements_analyzer->data_flow_graph->addNode($new_parent_node);

                    $stmt_type->parent_nodes[$new_parent_node->id] = $new_parent_node;

                    if ($casted_part_type->parent_nodes) {
                        foreach ($casted_part_type->parent_nodes as $parent_node) {
                            $statements_analyzer->data_flow_graph->addPath($parent_node, $new_parent_node, 'concat');
                        }
                    }
                }
            }
        }

        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        return true;
    }
}
