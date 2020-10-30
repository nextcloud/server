<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Fetch;

use PhpParser;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\TaintSource;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ImpureVariable;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\PossiblyUndefinedGlobalVariable;
use Psalm\Issue\PossiblyUndefinedVariable;
use Psalm\Issue\UndefinedGlobalVariable;
use Psalm\Issue\UndefinedVariable;
use Psalm\IssueBuffer;
use Psalm\Type;
use function is_string;
use function in_array;

/**
 * @internal
 */
class VariableFetchAnalyzer
{
    public const SUPER_GLOBALS = [
        '$GLOBALS',
        '$_SERVER',
        '$_GET',
        '$_POST',
        '$_FILES',
        '$_COOKIE',
        '$_SESSION',
        '$_REQUEST',
        '$_ENV',
        '$http_response_header',
    ];

    /**
     * @param bool $from_global - when used in a global keyword
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Variable $stmt,
        Context $context,
        bool $passed_by_reference = false,
        ?Type\Union $by_ref_type = null,
        bool $array_assignment = false,
        bool $from_global = false
    ) : bool {
        $project_analyzer = $statements_analyzer->getFileAnalyzer()->project_analyzer;
        $codebase = $statements_analyzer->getCodebase();

        if ($stmt->name === 'this') {
            if ($statements_analyzer->isStatic()) {
                if (IssueBuffer::accepts(
                    new InvalidScope(
                        'Invalid reference to $this in a static context',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }

                return true;
            }

            if (!isset($context->vars_in_scope['$this'])) {
                if (IssueBuffer::accepts(
                    new InvalidScope(
                        'Invalid reference to $this in a non-class context',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }

                $context->vars_in_scope['$this'] = Type::getMixed();
                $context->vars_possibly_in_scope['$this'] = true;

                return true;
            }

            $statements_analyzer->node_data->setType($stmt, clone $context->vars_in_scope['$this']);

            if ($codebase->store_node_types
                    && !$context->collect_initializations
                    && !$context->collect_mutations
                && ($stmt_type = $statements_analyzer->node_data->getType($stmt))
            ) {
                $codebase->analyzer->addNodeType(
                    $statements_analyzer->getFilePath(),
                    $stmt,
                    $stmt_type->getId()
                );
            }

            if (!$context->collect_mutations && !$context->collect_initializations) {
                if ($context->pure) {
                    if (IssueBuffer::accepts(
                        new ImpureVariable(
                            'Cannot reference $this in a pure context',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } elseif ($statements_analyzer->getSource() instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
                    && $statements_analyzer->getSource()->track_mutations
                ) {
                    $statements_analyzer->getSource()->inferred_impure = true;
                }
            }

            return true;
        }

        if (!$context->check_variables) {
            if (is_string($stmt->name)) {
                $var_name = '$' . $stmt->name;

                if (!$context->hasVariable($var_name)) {
                    $context->vars_in_scope[$var_name] = Type::getMixed();
                    $context->vars_possibly_in_scope[$var_name] = true;
                    $statements_analyzer->node_data->setType($stmt, Type::getMixed());
                } else {
                    $stmt_type = clone $context->vars_in_scope[$var_name];

                    $statements_analyzer->node_data->setType($stmt, $stmt_type);

                    self::addDataFlowToVariable($statements_analyzer, $stmt, $var_name, $stmt_type, $context);
                }
            } else {
                $statements_analyzer->node_data->setType($stmt, Type::getMixed());
            }

            return true;
        }

        if (is_string($stmt->name) && self::isSuperGlobal('$' . $stmt->name)) {
            $var_name = '$' . $stmt->name;

            if (isset($context->vars_in_scope[$var_name])) {
                $type = clone $context->vars_in_scope[$var_name];

                self::taintVariable($statements_analyzer, $var_name, $type, $stmt);

                $statements_analyzer->node_data->setType($stmt, $type);

                return true;
            }

            $type = self::getGlobalType($var_name);

            self::taintVariable($statements_analyzer, $var_name, $type, $stmt);

            $statements_analyzer->node_data->setType($stmt, $type);
            $context->vars_in_scope[$var_name] = clone $type;
            $context->vars_possibly_in_scope[$var_name] = true;

            return true;
        }

        if (!is_string($stmt->name)) {
            if ($context->pure) {
                if (IssueBuffer::accepts(
                    new ImpureVariable(
                        'Cannot reference an unknown variable in a pure context',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } elseif ($statements_analyzer->getSource() instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
                && $statements_analyzer->getSource()->track_mutations
            ) {
                $statements_analyzer->getSource()->inferred_impure = true;
            }

            $was_inside_use = $context->inside_use;
            $context->inside_use = true;
            $expr_result = ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context);
            $context->inside_use = $was_inside_use;

            return $expr_result;
        }

        if ($passed_by_reference && $by_ref_type) {
            AssignmentAnalyzer::assignByRefParam(
                $statements_analyzer,
                $stmt,
                $by_ref_type,
                $by_ref_type,
                $context
            );

            return true;
        }

        $var_name = '$' . $stmt->name;

        if (!$context->hasVariable($var_name)) {
            if (!isset($context->vars_possibly_in_scope[$var_name])
                || !$statements_analyzer->getFirstAppearance($var_name)
            ) {
                if ($array_assignment) {
                    // if we're in an array assignment, let's assign the variable
                    // because PHP allows it

                    $context->vars_in_scope[$var_name] = Type::getArray();
                    $context->vars_possibly_in_scope[$var_name] = true;

                    // it might have been defined first in another if/else branch
                    if (!$statements_analyzer->hasVariable($var_name)) {
                        $statements_analyzer->registerVariable(
                            $var_name,
                            new CodeLocation($statements_analyzer, $stmt),
                            $context->branch_point
                        );
                    }
                } elseif (!$context->inside_isset
                    || $statements_analyzer->getSource() instanceof FunctionLikeAnalyzer
                ) {
                    if ($context->is_global || $from_global) {
                        if (IssueBuffer::accepts(
                            new UndefinedGlobalVariable(
                                'Cannot find referenced variable ' . $var_name . ' in global scope',
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                $var_name
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }

                        $statements_analyzer->node_data->setType($stmt, Type::getMixed());

                        return true;
                    }

                    if (IssueBuffer::accepts(
                        new UndefinedVariable(
                            'Cannot find referenced variable ' . $var_name,
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    $statements_analyzer->node_data->setType($stmt, Type::getMixed());

                    return true;
                }
            }

            $first_appearance = $statements_analyzer->getFirstAppearance($var_name);

            if ($first_appearance && !$context->inside_isset && !$context->inside_unset) {
                if ($context->is_global) {
                    if ($codebase->alter_code) {
                        if (!isset($project_analyzer->getIssuesToFix()['PossiblyUndefinedGlobalVariable'])) {
                            return true;
                        }

                        $branch_point = $statements_analyzer->getBranchPoint($var_name);

                        if ($branch_point) {
                            $statements_analyzer->addVariableInitialization($var_name, $branch_point);
                        }

                        return true;
                    }

                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedGlobalVariable(
                            'Possibly undefined global variable ' . $var_name . ', first seen on line ' .
                                $first_appearance->getLineNumber(),
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $var_name
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                        (bool) $statements_analyzer->getBranchPoint($var_name)
                    )) {
                        // fall through
                    }
                } else {
                    if ($codebase->alter_code) {
                        if (!isset($project_analyzer->getIssuesToFix()['PossiblyUndefinedVariable'])) {
                            return true;
                        }

                        $branch_point = $statements_analyzer->getBranchPoint($var_name);

                        if ($branch_point) {
                            $statements_analyzer->addVariableInitialization($var_name, $branch_point);
                        }

                        return true;
                    }

                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable ' . $var_name . ', first seen on line ' .
                                $first_appearance->getLineNumber(),
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                        (bool) $statements_analyzer->getBranchPoint($var_name)
                    )) {
                        // fall through
                    }
                }

                if ($codebase->store_node_types
                    && !$context->collect_initializations
                    && !$context->collect_mutations
                ) {
                    $codebase->analyzer->addNodeReference(
                        $statements_analyzer->getFilePath(),
                        $stmt,
                        $first_appearance->raw_file_start . '-' . $first_appearance->raw_file_end . ':mixed'
                    );
                }

                $stmt_type = Type::getMixed();

                $statements_analyzer->node_data->setType($stmt, $stmt_type);

                self::addDataFlowToVariable($statements_analyzer, $stmt, $var_name, $stmt_type, $context);

                $statements_analyzer->registerPossiblyUndefinedVariable($var_name, $stmt);

                return true;
            }
        } else {
            $stmt_type = clone $context->vars_in_scope[$var_name];

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            self::addDataFlowToVariable($statements_analyzer, $stmt, $var_name, $stmt_type, $context);

            if ($stmt_type->possibly_undefined_from_try && !$context->inside_isset) {
                if ($context->is_global) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedGlobalVariable(
                            'Possibly undefined global variable ' . $var_name . ' defined in try block',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $var_name
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable ' . $var_name . ' defined in try block',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeType(
                    $statements_analyzer->getFilePath(),
                    $stmt,
                    $stmt_type->getId()
                );
            }

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $first_appearance = $statements_analyzer->getFirstAppearance($var_name);

                if ($first_appearance) {
                    $codebase->analyzer->addNodeReference(
                        $statements_analyzer->getFilePath(),
                        $stmt,
                        $first_appearance->raw_file_start
                            . '-' . $first_appearance->raw_file_end
                            . ':' . $stmt_type->getId()
                    );
                }
            }
        }

        return true;
    }

    private static function addDataFlowToVariable(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Variable $stmt,
        string $var_name,
        Type\Union $stmt_type,
        Context $context
    ) : void {
        $codebase = $statements_analyzer->getCodebase();

        if ($statements_analyzer->data_flow_graph
            && $codebase->find_unused_variables
            && ($context->inside_call
                || $context->inside_conditional
                || $context->inside_use
                || $context->inside_isset)
        ) {
            if (!$stmt_type->parent_nodes) {
                $assignment_node = DataFlowNode::getForAssignment(
                    $var_name,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                );

                $stmt_type->parent_nodes = [
                    $assignment_node->id => $assignment_node
                ];
            }

            foreach ($stmt_type->parent_nodes as $parent_node) {
                if ($context->inside_call) {
                    $statements_analyzer->data_flow_graph->addPath(
                        $parent_node,
                        new DataFlowNode(
                            'variable-use',
                            'variable use',
                            null
                        ),
                        'use-inside-call'
                    );
                } elseif ($context->inside_conditional) {
                    $statements_analyzer->data_flow_graph->addPath(
                        $parent_node,
                        new DataFlowNode(
                            'variable-use',
                            'variable use',
                            null
                        ),
                        'use-inside-conditional'
                    );
                } elseif ($context->inside_isset) {
                    $statements_analyzer->data_flow_graph->addPath(
                        $parent_node,
                        new DataFlowNode(
                            'variable-use',
                            'variable use',
                            null
                        ),
                        'use-inside-isset'
                    );
                } else {
                    $statements_analyzer->data_flow_graph->addPath(
                        $parent_node,
                        new DataFlowNode(
                            'variable-use',
                            'variable use',
                            null
                        ),
                        'variable-use'
                    );
                }
            }
        }
    }

    private static function taintVariable(
        StatementsAnalyzer $statements_analyzer,
        string $var_name,
        Type\Union $type,
        PhpParser\Node\Expr\Variable $stmt
    ) : void {
        if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
            && !\in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
        ) {
            if ($var_name === '$_GET'
                || $var_name === '$_POST'
                || $var_name === '$_COOKIE'
                || $var_name === '$_REQUEST'
            ) {
                $taint_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

                $server_taint_source = new TaintSource(
                    $var_name . ':' . $taint_location->file_name . ':' . $taint_location->raw_file_start,
                    $var_name,
                    null,
                    null,
                    Type\TaintKindGroup::ALL_INPUT
                );

                $statements_analyzer->data_flow_graph->addSource($server_taint_source);

                $type->parent_nodes = [
                    $server_taint_source->id => $server_taint_source
                ];
            }
        }
    }

    /**
     * @psalm-pure
     */
    public static function isSuperGlobal(string $var_id) : bool
    {
        return in_array(
            $var_id,
            self::SUPER_GLOBALS,
            true
        );
    }

    public static function getGlobalType(string $var_id) : Type\Union
    {
        $config = \Psalm\Config::getInstance();

        if (isset($config->globals[$var_id])) {
            return Type::parseString($config->globals[$var_id]);
        }

        if ($var_id === '$argv') {
            return new Type\Union([
                new Type\Atomic\TArray([Type::getInt(), Type::getString()]),
            ]);
        }

        if ($var_id === '$argc') {
            return Type::getInt();
        }

        if (self::isSuperGlobal($var_id)) {
            $type = Type::getArray();

            return $type;
        }

        return Type::getMixed();
    }
}
