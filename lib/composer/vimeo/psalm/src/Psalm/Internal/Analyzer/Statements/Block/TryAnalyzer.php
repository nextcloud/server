<?php
namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InvalidCatch;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;
use Psalm\Internal\Scope\FinallyScope;
use function in_array;
use function array_merge;
use function array_intersect_key;
use function is_string;
use function strtolower;
use function array_map;
use function version_compare;
use const PHP_VERSION;

/**
 * @internal
 */
class TryAnalyzer
{
    /**
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\TryCatch $stmt,
        Context $context
    ): ?bool {
        $catch_actions = [];
        $all_catches_leave = true;

        $codebase = $statements_analyzer->getCodebase();

        /** @var int $i */
        foreach ($stmt->catches as $i => $catch) {
            $catch_actions[$i] = ScopeAnalyzer::getControlActions(
                $catch->stmts,
                $statements_analyzer->node_data,
                $codebase->config->exit_functions
            );
            $all_catches_leave = $all_catches_leave && !in_array(ScopeAnalyzer::ACTION_NONE, $catch_actions[$i], true);
        }

        $existing_thrown_exceptions = $context->possibly_thrown_exceptions;

        /**
         * @var array<string, array<array-key, CodeLocation>>
         */
        $context->possibly_thrown_exceptions = [];

        $old_context = clone $context;

        if ($all_catches_leave && !$stmt->finally) {
            $try_context = $context;
        } else {
            $try_context = clone $context;

            if ($codebase->alter_code) {
                $try_context->branch_point = $try_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
            }

            if ($stmt->finally) {
                $try_context->finally_scope = new FinallyScope($try_context->vars_in_scope);
            }
        }

        $assigned_var_ids = $try_context->assigned_var_ids;
        $context->assigned_var_ids = [];

        $old_referenced_var_ids = $try_context->referenced_var_ids;

        if ($statements_analyzer->analyze($stmt->stmts, $context) === false) {
            return false;
        }

        if ($try_context->finally_scope) {
            foreach ($context->vars_in_scope as $var_id => $type) {
                if (isset($try_context->finally_scope->vars_in_scope[$var_id])) {
                    if ($try_context->finally_scope->vars_in_scope[$var_id] !== $type) {
                        $try_context->finally_scope->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $try_context->finally_scope->vars_in_scope[$var_id],
                            $type,
                            $statements_analyzer->getCodebase()
                        );
                    }
                } else {
                    $try_context->finally_scope->vars_in_scope[$var_id] = $type;
                }
            }
        }

        $context->has_returned = false;

        $stmt_control_actions = ScopeAnalyzer::getControlActions(
            $stmt->stmts,
            $statements_analyzer->node_data,
            $codebase->config->exit_functions,
            $context->break_types
        );

        /** @var array<string, bool> */
        $newly_assigned_var_ids = $context->assigned_var_ids;

        $context->assigned_var_ids = array_merge(
            $assigned_var_ids,
            $newly_assigned_var_ids
        );

        $possibly_referenced_var_ids = array_merge(
            $context->referenced_var_ids,
            $old_referenced_var_ids
        );

        if ($try_context !== $context) {
            foreach ($context->vars_in_scope as $var_id => $type) {
                if (!isset($try_context->vars_in_scope[$var_id])) {
                    $try_context->vars_in_scope[$var_id] = clone $type;

                    $context->vars_in_scope[$var_id]->possibly_undefined = true;
                    $context->vars_in_scope[$var_id]->possibly_undefined_from_try = true;
                } else {
                    $try_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                        $try_context->vars_in_scope[$var_id],
                        $type
                    );
                }
            }

            $try_context->vars_possibly_in_scope = $context->vars_possibly_in_scope;
            $try_context->possibly_thrown_exceptions = $context->possibly_thrown_exceptions;

            $context->referenced_var_ids = array_intersect_key(
                $try_context->referenced_var_ids,
                $context->referenced_var_ids
            );
        }

        $try_leaves_loop = $context->loop_scope
            && $context->loop_scope->final_actions
            && !in_array(ScopeAnalyzer::ACTION_NONE, $context->loop_scope->final_actions, true);

        if (!$all_catches_leave) {
            foreach ($newly_assigned_var_ids as $assigned_var_id => $_) {
                $context->removeVarFromConflictingClauses($assigned_var_id);
            }
        } else {
            foreach ($newly_assigned_var_ids as $assigned_var_id => $_) {
                $try_context->removeVarFromConflictingClauses($assigned_var_id);
            }
        }

        // at this point we have two contexts – $context, in which it is assumed that everything was fine,
        // and $try_context - which allows all variables to have the union of the values before and after
        // the try was applied
        $original_context = clone $try_context;

        $issues_to_suppress = [
            'RedundantCondition',
            'RedundantConditionGivenDocblockType',
            'TypeDoesNotContainNull',
            'TypeDoesNotContainType',
        ];

        $definitely_newly_assigned_var_ids = $newly_assigned_var_ids;

        /** @var int $i */
        foreach ($stmt->catches as $i => $catch) {
            $catch_context = clone $original_context;
            $catch_context->has_returned = false;

            foreach ($catch_context->vars_in_scope as $var_id => $type) {
                if (!isset($old_context->vars_in_scope[$var_id])) {
                    $type = clone $type;
                    $type->possibly_undefined_from_try = true;
                    $catch_context->vars_in_scope[$var_id] = $type;
                } else {
                    $catch_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                        $type,
                        $old_context->vars_in_scope[$var_id]
                    );
                }
            }

            $fq_catch_classes = [];

            if (!$catch->types) {
                throw new \UnexpectedValueException('Very bad');
            }

            foreach ($catch->types as $catch_type) {
                $fq_catch_class = ClassLikeAnalyzer::getFQCLNFromNameObject(
                    $catch_type,
                    $statements_analyzer->getAliases()
                );

                $fq_catch_class = $codebase->classlikes->getUnAliasedName($fq_catch_class);

                if ($codebase->alter_code && $fq_catch_class) {
                    $codebase->classlikes->handleClassLikeReferenceInMigration(
                        $codebase,
                        $statements_analyzer,
                        $catch_type,
                        $fq_catch_class,
                        $context->calling_method_id
                    );
                }

                if ($original_context->check_classes) {
                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $statements_analyzer,
                        $fq_catch_class,
                        new CodeLocation($statements_analyzer->getSource(), $catch_type, $context->include_location),
                        $context->self,
                        $context->calling_method_id,
                        $statements_analyzer->getSuppressedIssues(),
                        false
                    ) === false) {
                        return false;
                    }
                }

                if (($codebase->classExists($fq_catch_class)
                        && strtolower($fq_catch_class) !== 'exception'
                        && !($codebase->classExtends($fq_catch_class, 'Exception')
                            || $codebase->classImplements($fq_catch_class, 'Throwable')))
                    || ($codebase->interfaceExists($fq_catch_class)
                        && strtolower($fq_catch_class) !== 'throwable'
                        && !$codebase->interfaceExtends($fq_catch_class, 'Throwable'))
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidCatch(
                            'Class/interface ' . $fq_catch_class . ' cannot be caught',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $fq_catch_class
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }

                $fq_catch_classes[] = $fq_catch_class;
            }

            if ($catch_context->collect_exceptions) {
                foreach ($fq_catch_classes as $fq_catch_class) {
                    $fq_catch_class_lower = strtolower($fq_catch_class);

                    foreach ($catch_context->possibly_thrown_exceptions as $exception_fqcln => $_) {
                        $exception_fqcln_lower = strtolower($exception_fqcln);

                        if ($exception_fqcln_lower === $fq_catch_class_lower
                            || ($codebase->classExists($exception_fqcln)
                                && $codebase->classExtendsOrImplements($exception_fqcln, $fq_catch_class))
                            || ($codebase->interfaceExists($exception_fqcln)
                                && $codebase->interfaceExtends($exception_fqcln, $fq_catch_class))
                        ) {
                            unset($original_context->possibly_thrown_exceptions[$exception_fqcln]);
                            unset($context->possibly_thrown_exceptions[$exception_fqcln]);
                            unset($catch_context->possibly_thrown_exceptions[$exception_fqcln]);
                        }
                    }
                }

                /**
                 * @var array<string, array<array-key, CodeLocation>>
                 */
                $catch_context->possibly_thrown_exceptions = [];
            }

            // discard all clauses because crazy stuff may have happened in try block
            $catch_context->clauses = [];

            /** @psalm-suppress RedundantConditionGivenDocblockType */
            if ($catch->var && is_string($catch->var->name)) {
                $catch_var_id = '$' . $catch->var->name;

                $catch_context->vars_in_scope[$catch_var_id] = new Union(
                    array_map(
                        /**
                         * @param string $fq_catch_class
                         */
                        function ($fq_catch_class) use ($codebase): \Psalm\Type\Atomic\TNamedObject {
                            $catch_class_type = new TNamedObject($fq_catch_class);

                            if (version_compare(PHP_VERSION, '7.0.0dev', '>=')
                                && strtolower($fq_catch_class) !== 'throwable'
                                && $codebase->interfaceExists($fq_catch_class)
                                && !$codebase->interfaceExtends($fq_catch_class, 'Throwable')
                            ) {
                                $catch_class_type->addIntersectionType(new TNamedObject('Throwable'));
                            }

                            return $catch_class_type;
                        },
                        $fq_catch_classes
                    )
                );

                $catch_context->vars_possibly_in_scope[$catch_var_id] = true;

                $location = new CodeLocation($statements_analyzer->getSource(), $catch->var);

                if (!$statements_analyzer->hasVariable($catch_var_id)) {
                    $statements_analyzer->registerVariable(
                        $catch_var_id,
                        $location,
                        $catch_context->branch_point
                    );
                } else {
                    $statements_analyzer->registerVariableAssignment(
                        $catch_var_id,
                        $location
                    );
                }

                if ($statements_analyzer->data_flow_graph) {
                    $catch_var_node = DataFlowNode::getForAssignment($catch_var_id, $location);

                    $catch_context->vars_in_scope[$catch_var_id]->parent_nodes = [
                        $catch_var_node->id => $catch_var_node
                    ];

                    if ($statements_analyzer->data_flow_graph instanceof \Psalm\Internal\Codebase\VariableUseGraph) {
                        $statements_analyzer->data_flow_graph->addPath(
                            $catch_var_node,
                            new DataFlowNode('variable-use', 'variable use', null),
                            'variable-use'
                        );
                    }
                }
            }

            $suppressed_issues = $statements_analyzer->getSuppressedIssues();

            foreach ($issues_to_suppress as $issue_to_suppress) {
                if (!in_array($issue_to_suppress, $suppressed_issues, true)) {
                    $statements_analyzer->addSuppressedIssues([$issue_to_suppress]);
                }
            }

            $old_catch_assigned_var_ids = $catch_context->referenced_var_ids;

            $catch_context->assigned_var_ids = [];

            $statements_analyzer->analyze($catch->stmts, $catch_context);

            // recalculate in case there's a no-return clause
            $catch_actions[$i] = ScopeAnalyzer::getControlActions(
                $catch->stmts,
                $statements_analyzer->node_data,
                $codebase->config->exit_functions,
                $context->break_types
            );

            foreach ($issues_to_suppress as $issue_to_suppress) {
                if (!in_array($issue_to_suppress, $suppressed_issues, true)) {
                    $statements_analyzer->removeSuppressedIssues([$issue_to_suppress]);
                }
            }

            /** @var array<string, bool> */
            $new_catch_assigned_var_ids = $catch_context->assigned_var_ids;

            $catch_context->assigned_var_ids += $old_catch_assigned_var_ids;

            $context->referenced_var_ids = array_intersect_key(
                $catch_context->referenced_var_ids,
                $context->referenced_var_ids
            );

            $possibly_referenced_var_ids = array_merge(
                $catch_context->referenced_var_ids,
                $possibly_referenced_var_ids
            );

            if ($codebase->find_unused_variables && $catch_actions[$i] !== [ScopeAnalyzer::ACTION_END]) {
                // something
            }

            if ($catch_context->collect_exceptions) {
                $context->mergeExceptions($catch_context);
            }

            if ($catch_actions[$i] !== [ScopeAnalyzer::ACTION_END]
                && $catch_actions[$i] !== [ScopeAnalyzer::ACTION_CONTINUE]
                && $catch_actions[$i] !== [ScopeAnalyzer::ACTION_BREAK]
            ) {
                $definitely_newly_assigned_var_ids = array_intersect_key(
                    $new_catch_assigned_var_ids,
                    $definitely_newly_assigned_var_ids
                );

                foreach ($catch_context->vars_in_scope as $var_id => $type) {
                    if ($stmt_control_actions === [ScopeAnalyzer::ACTION_END]) {
                        $context->vars_in_scope[$var_id] = $type;
                    } elseif (isset($context->vars_in_scope[$var_id])) {
                        $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $context->vars_in_scope[$var_id],
                            $type
                        );
                    }
                }

                $context->vars_possibly_in_scope = array_merge(
                    $catch_context->vars_possibly_in_scope,
                    $context->vars_possibly_in_scope
                );
            } else {
                if ($stmt->finally) {
                    $context->vars_possibly_in_scope = array_merge(
                        $catch_context->vars_possibly_in_scope,
                        $context->vars_possibly_in_scope
                    );
                }
            }

            if ($try_context->finally_scope) {
                foreach ($catch_context->vars_in_scope as $var_id => $type) {
                    if (isset($try_context->finally_scope->vars_in_scope[$var_id])) {
                        if ($try_context->finally_scope->vars_in_scope[$var_id] !== $type) {
                            $try_context->finally_scope->vars_in_scope[$var_id] = Type::combineUnionTypes(
                                $try_context->finally_scope->vars_in_scope[$var_id],
                                $type,
                                $statements_analyzer->getCodebase()
                            );
                        }
                    } else {
                        $try_context->finally_scope->vars_in_scope[$var_id] = $type;
                    }
                }
            }
        }

        if ($context->loop_scope
            && !$try_leaves_loop
            && !in_array(ScopeAnalyzer::ACTION_NONE, $context->loop_scope->final_actions, true)
        ) {
            $context->loop_scope->final_actions[] = ScopeAnalyzer::ACTION_NONE;
        }

        if ($stmt->finally) {
            if ($try_context->finally_scope) {
                $finally_context = clone $context;

                $finally_context->assigned_var_ids = [];
                $finally_context->possibly_assigned_var_ids = [];

                $finally_context->vars_in_scope = $try_context->finally_scope->vars_in_scope;

                $statements_analyzer->analyze($stmt->finally->stmts, $finally_context);

                if ($finally_context->has_returned) {
                    $context->has_returned = true;
                }

                /** @var string $var_id */
                foreach ($finally_context->assigned_var_ids as $var_id => $_) {
                    if (isset($context->vars_in_scope[$var_id])) {
                        if ($context->vars_in_scope[$var_id]->possibly_undefined
                            && $context->vars_in_scope[$var_id]->possibly_undefined_from_try
                        ) {
                            $context->vars_in_scope[$var_id]->possibly_undefined = false;
                            $context->vars_in_scope[$var_id]->possibly_undefined_from_try = false;
                        }

                        $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $context->vars_in_scope[$var_id],
                            $finally_context->vars_in_scope[$var_id],
                            $codebase
                        );
                    } elseif (isset($finally_context->vars_in_scope[$var_id])) {
                        $context->vars_in_scope[$var_id] = clone $finally_context->vars_in_scope[$var_id];
                    }
                }
            }
        }

        foreach ($definitely_newly_assigned_var_ids as $var_id => $_) {
            if (isset($context->vars_in_scope[$var_id])) {
                $new_type = clone $context->vars_in_scope[$var_id];

                if ($new_type->possibly_undefined_from_try) {
                    $new_type->possibly_undefined = false;
                    $new_type->possibly_undefined_from_try = false;
                }

                $context->vars_in_scope[$var_id] = $new_type;
            }
        }

        foreach ($existing_thrown_exceptions as $possibly_thrown_exception => $codelocations) {
            foreach ($codelocations as $hash => $codelocation) {
                $context->possibly_thrown_exceptions[$possibly_thrown_exception][$hash] = $codelocation;
            }
        }

        return null;
    }
}
