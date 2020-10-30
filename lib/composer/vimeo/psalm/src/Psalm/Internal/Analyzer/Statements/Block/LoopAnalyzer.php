<?php
namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Clause;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Internal\Scope\LoopScope;
use Psalm\Type;
use Psalm\Type\Algebra;
use Psalm\Type\Reconciler;
use function array_merge;
use function array_keys;
use function array_unique;
use function array_intersect_key;
use function in_array;

/**
 * @internal
 */
class LoopAnalyzer
{
    /**
     * Checks an array of statements in a loop
     *
     * @param  array<PhpParser\Node\Stmt>   $stmts
     * @param  PhpParser\Node\Expr[]        $pre_conditions
     * @param  PhpParser\Node\Expr[]        $post_expressions
     *
     * @return false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        array $stmts,
        array $pre_conditions,
        array $post_expressions,
        LoopScope $loop_scope,
        Context &$inner_context = null,
        bool $is_do = false,
        bool $always_enters_loop = false
    ): ?bool {
        $traverser = new PhpParser\NodeTraverser;

        $assignment_mapper = new \Psalm\Internal\PhpVisitor\AssignmentMapVisitor($loop_scope->loop_context->self);
        $traverser->addVisitor($assignment_mapper);

        $traverser->traverse(array_merge($pre_conditions, $stmts, $post_expressions));

        $assignment_map = $assignment_mapper->getAssignmentMap();

        $assignment_depth = 0;

        $always_assigned_before_loop_body_vars = [];

        $pre_condition_clauses = [];

        $original_protected_var_ids = $loop_scope->loop_parent_context->protected_var_ids;

        $codebase = $statements_analyzer->getCodebase();

        $inner_do_context = null;

        if ($pre_conditions) {
            foreach ($pre_conditions as $i => $pre_condition) {
                $pre_condition_id = \spl_object_id($pre_condition);

                $pre_condition_clauses[$i] = Algebra::getFormula(
                    $pre_condition_id,
                    $pre_condition_id,
                    $pre_condition,
                    $loop_scope->loop_context->self,
                    $statements_analyzer,
                    $codebase
                );
            }
        } else {
            $always_assigned_before_loop_body_vars = Context::getNewOrUpdatedVarIds(
                $loop_scope->loop_parent_context,
                $loop_scope->loop_context
            );
        }

        $final_actions = ScopeAnalyzer::getControlActions(
            $stmts,
            $statements_analyzer->node_data,
            Config::getInstance()->exit_functions,
            $loop_scope->loop_context->break_types
        );

        $does_always_break = $final_actions === [ScopeAnalyzer::ACTION_BREAK];

        $has_continue = in_array(ScopeAnalyzer::ACTION_CONTINUE, $final_actions, true);

        if ($assignment_map) {
            $first_var_id = array_keys($assignment_map)[0];

            $assignment_depth = self::getAssignmentMapDepth($first_var_id, $assignment_map);
        }

        if ($has_continue) {
            // this intuuitively feels right to me – if there's a continue statement,
            // maybe more assignment intrigue is possible
            $assignment_depth++;
        }

        $loop_scope->loop_context->parent_context = $loop_scope->loop_parent_context;

        $pre_outer_context = $loop_scope->loop_parent_context;

        if ($assignment_depth === 0 || $does_always_break) {
            $inner_context = clone $loop_scope->loop_context;

            foreach ($inner_context->vars_in_scope as $context_var_id => $context_type) {
                $inner_context->vars_in_scope[$context_var_id] = clone $context_type;
            }

            $inner_context->loop_scope = $loop_scope;

            $inner_context->parent_context = $loop_scope->loop_context;
            $old_referenced_var_ids = $inner_context->referenced_var_ids;
            $inner_context->referenced_var_ids = [];

            foreach ($pre_conditions as $condition_offset => $pre_condition) {
                self::applyPreConditionToLoopContext(
                    $statements_analyzer,
                    $pre_condition,
                    $pre_condition_clauses[$condition_offset],
                    $inner_context,
                    $loop_scope->loop_parent_context,
                    $is_do
                );
            }

            $inner_context->protected_var_ids = $loop_scope->protected_var_ids;

            $statements_analyzer->analyze($stmts, $inner_context);
            self::updateLoopScopeContexts($loop_scope, $loop_scope->loop_parent_context);

            foreach ($post_expressions as $post_expression) {
                if (ExpressionAnalyzer::analyze(
                    $statements_analyzer,
                    $post_expression,
                    $loop_scope->loop_context
                ) === false
                ) {
                    return false;
                }
            }

            $inner_context->referenced_var_ids = $old_referenced_var_ids + $inner_context->referenced_var_ids;

            $loop_scope->loop_parent_context->vars_possibly_in_scope = array_merge(
                $inner_context->vars_possibly_in_scope,
                $loop_scope->loop_parent_context->vars_possibly_in_scope
            );
        } else {
            $pre_outer_context = clone $loop_scope->loop_parent_context;

            $analyzer = $statements_analyzer->getCodebase()->analyzer;

            $original_mixed_counts = $analyzer->getMixedCountsForFile($statements_analyzer->getFilePath());

            $pre_condition_vars_in_scope = $loop_scope->loop_context->vars_in_scope;

            IssueBuffer::startRecording();

            if (!$is_do) {
                foreach ($pre_conditions as $condition_offset => $pre_condition) {
                    self::applyPreConditionToLoopContext(
                        $statements_analyzer,
                        $pre_condition,
                        $pre_condition_clauses[$condition_offset],
                        $loop_scope->loop_context,
                        $loop_scope->loop_parent_context,
                        $is_do
                    );
                }
            }

            // record all the vars that existed before we did the first pass through the loop
            $pre_loop_context = clone $loop_scope->loop_context;

            $inner_context = clone $loop_scope->loop_context;

            foreach ($inner_context->vars_in_scope as $context_var_id => $context_type) {
                $inner_context->vars_in_scope[$context_var_id] = clone $context_type;
            }

            $inner_context->parent_context = $loop_scope->loop_context;
            $inner_context->loop_scope = $loop_scope;

            $old_referenced_var_ids = $inner_context->referenced_var_ids;
            $inner_context->referenced_var_ids = [];

            $inner_context->protected_var_ids = $loop_scope->protected_var_ids;

            $statements_analyzer->analyze($stmts, $inner_context);

            self::updateLoopScopeContexts($loop_scope, $pre_outer_context);

            $inner_context->protected_var_ids = $original_protected_var_ids;

            if ($is_do) {
                $inner_do_context = clone $inner_context;

                foreach ($pre_conditions as $condition_offset => $pre_condition) {
                    $always_assigned_before_loop_body_vars = array_merge(
                        self::applyPreConditionToLoopContext(
                            $statements_analyzer,
                            $pre_condition,
                            $pre_condition_clauses[$condition_offset],
                            $inner_context,
                            $loop_scope->loop_parent_context,
                            $is_do
                        ),
                        $always_assigned_before_loop_body_vars
                    );
                }
            }

            $always_assigned_before_loop_body_vars = array_unique($always_assigned_before_loop_body_vars);

            foreach ($post_expressions as $post_expression) {
                if (ExpressionAnalyzer::analyze($statements_analyzer, $post_expression, $inner_context) === false) {
                    return false;
                }
            }

            $inner_context->referenced_var_ids = array_intersect_key(
                $old_referenced_var_ids,
                $inner_context->referenced_var_ids
            );

            $recorded_issues = IssueBuffer::clearRecordingLevel();
            IssueBuffer::stopRecording();

            for ($i = 0; $i < $assignment_depth; ++$i) {
                $vars_to_remove = [];

                $loop_scope->iteration_count++;

                $has_changes = false;

                // reset the $inner_context to what it was before we started the analysis,
                // but union the types with what's in the loop scope

                foreach ($inner_context->vars_in_scope as $var_id => $type) {
                    if (in_array($var_id, $always_assigned_before_loop_body_vars, true)) {
                        // set the vars to whatever the while/foreach loop expects them to be
                        if (!isset($pre_loop_context->vars_in_scope[$var_id])
                            || !$type->equals($pre_loop_context->vars_in_scope[$var_id])
                        ) {
                            $has_changes = true;
                        }
                    } elseif (isset($pre_outer_context->vars_in_scope[$var_id])) {
                        if (!$type->equals($pre_outer_context->vars_in_scope[$var_id])) {
                            $has_changes = true;

                            // widen the foreach context type with the initial context type
                            $inner_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                                $inner_context->vars_in_scope[$var_id],
                                $pre_outer_context->vars_in_scope[$var_id]
                            );

                            // if there's a change, invalidate related clauses
                            $pre_loop_context->removeVarFromConflictingClauses($var_id);

                            $loop_scope->loop_parent_context->possibly_assigned_var_ids[$var_id] = true;
                        }

                        if (isset($loop_scope->loop_context->vars_in_scope[$var_id])
                            && !$type->equals($loop_scope->loop_context->vars_in_scope[$var_id])
                        ) {
                            $has_changes = true;

                            // widen the foreach context type with the initial context type
                            $inner_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                                $inner_context->vars_in_scope[$var_id],
                                $loop_scope->loop_context->vars_in_scope[$var_id]
                            );

                            // if there's a change, invalidate related clauses
                            $pre_loop_context->removeVarFromConflictingClauses($var_id);
                        }
                    } else {
                        // give an opportunity to redeemed UndefinedVariable issues
                        if ($recorded_issues) {
                            $has_changes = true;
                        }

                        // if we're in a do block we don't want to remove vars before evaluating
                        // the where conditional
                        if (!$is_do) {
                            $vars_to_remove[] = $var_id;
                        }
                    }
                }

                $inner_context->has_returned = false;

                $loop_scope->loop_parent_context->vars_possibly_in_scope = array_merge(
                    $inner_context->vars_possibly_in_scope,
                    $loop_scope->loop_parent_context->vars_possibly_in_scope
                );

                // if there are no changes to the types, no need to re-examine
                if (!$has_changes) {
                    break;
                }

                // remove vars that were defined in the foreach
                foreach ($vars_to_remove as $var_id) {
                    unset($inner_context->vars_in_scope[$var_id]);
                }

                $inner_context->clauses = $pre_loop_context->clauses;

                $analyzer->setMixedCountsForFile($statements_analyzer->getFilePath(), $original_mixed_counts);
                IssueBuffer::startRecording();

                foreach ($pre_loop_context->vars_in_scope as $var_id => $_) {
                    if (!isset($pre_condition_vars_in_scope[$var_id])
                        && isset($inner_context->vars_in_scope[$var_id])
                        && \strpos($var_id, '->') === false
                        && \strpos($var_id, '[') === false
                    ) {
                        $inner_context->vars_in_scope[$var_id]->possibly_undefined = true;
                    }
                }

                if (!$is_do) {
                    foreach ($pre_conditions as $condition_offset => $pre_condition) {
                        self::applyPreConditionToLoopContext(
                            $statements_analyzer,
                            $pre_condition,
                            $pre_condition_clauses[$condition_offset],
                            $inner_context,
                            $loop_scope->loop_parent_context,
                            false
                        );
                    }
                }

                foreach ($always_assigned_before_loop_body_vars as $var_id) {
                    if ((!isset($inner_context->vars_in_scope[$var_id])
                            || $inner_context->vars_in_scope[$var_id]->getId()
                                !== $pre_loop_context->vars_in_scope[$var_id]->getId()
                            || $inner_context->vars_in_scope[$var_id]->from_docblock
                                !== $pre_loop_context->vars_in_scope[$var_id]->from_docblock
                        )
                    ) {
                        if (isset($pre_loop_context->vars_in_scope[$var_id])) {
                            $inner_context->vars_in_scope[$var_id] = clone $pre_loop_context->vars_in_scope[$var_id];
                        } else {
                            unset($inner_context->vars_in_scope[$var_id]);
                        }
                    }
                }

                $inner_context->clauses = $pre_loop_context->clauses;

                $inner_context->protected_var_ids = $loop_scope->protected_var_ids;

                $traverser = new PhpParser\NodeTraverser;

                $traverser->addVisitor(
                    new \Psalm\Internal\PhpVisitor\NodeCleanerVisitor(
                        $statements_analyzer->node_data
                    )
                );
                $traverser->traverse($stmts);

                $statements_analyzer->analyze($stmts, $inner_context);

                self::updateLoopScopeContexts($loop_scope, $pre_outer_context);

                $inner_context->protected_var_ids = $original_protected_var_ids;

                if ($is_do) {
                    $inner_do_context = clone $inner_context;

                    foreach ($pre_conditions as $condition_offset => $pre_condition) {
                        self::applyPreConditionToLoopContext(
                            $statements_analyzer,
                            $pre_condition,
                            $pre_condition_clauses[$condition_offset],
                            $inner_context,
                            $loop_scope->loop_parent_context,
                            $is_do
                        );
                    }
                }

                foreach ($post_expressions as $post_expression) {
                    if (ExpressionAnalyzer::analyze($statements_analyzer, $post_expression, $inner_context) === false) {
                        return false;
                    }
                }

                $recorded_issues = IssueBuffer::clearRecordingLevel();

                IssueBuffer::stopRecording();
            }

            if ($recorded_issues) {
                foreach ($recorded_issues as $recorded_issue) {
                    // if we're not in any loops then this will just result in the issue being emitted
                    IssueBuffer::bubbleUp($recorded_issue);
                }
            }
        }

        $does_sometimes_break = in_array(ScopeAnalyzer::ACTION_BREAK, $loop_scope->final_actions, true);
        $does_always_break = $loop_scope->final_actions === [ScopeAnalyzer::ACTION_BREAK];

        if ($does_sometimes_break) {
            if ($loop_scope->possibly_redefined_loop_parent_vars !== null) {
                foreach ($loop_scope->possibly_redefined_loop_parent_vars as $var => $type) {
                    $loop_scope->loop_parent_context->vars_in_scope[$var] = Type::combineUnionTypes(
                        $type,
                        $loop_scope->loop_parent_context->vars_in_scope[$var]
                    );

                    $loop_scope->loop_parent_context->possibly_assigned_var_ids[$var] = true;
                }
            }
        }

        foreach ($loop_scope->loop_parent_context->vars_in_scope as $var_id => $type) {
            if (!isset($loop_scope->loop_context->vars_in_scope[$var_id])) {
                continue;
            }

            if ($loop_scope->loop_context->vars_in_scope[$var_id]->getId() !== $type->getId()) {
                $loop_scope->loop_parent_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                    $loop_scope->loop_parent_context->vars_in_scope[$var_id],
                    $loop_scope->loop_context->vars_in_scope[$var_id]
                );

                $loop_scope->loop_parent_context->removeVarFromConflictingClauses($var_id);
            } else {
                $loop_scope->loop_parent_context->vars_in_scope[$var_id]->parent_nodes
                    += $loop_scope->loop_context->vars_in_scope[$var_id]->parent_nodes;
            }
        }

        if (!$does_always_break) {
            foreach ($loop_scope->loop_parent_context->vars_in_scope as $var_id => $type) {
                if (!isset($inner_context->vars_in_scope[$var_id])) {
                    unset($loop_scope->loop_parent_context->vars_in_scope[$var_id]);
                    continue;
                }

                if ($inner_context->vars_in_scope[$var_id]->hasMixed()) {
                    $inner_context->vars_in_scope[$var_id]->parent_nodes
                        += $loop_scope->loop_parent_context->vars_in_scope[$var_id]->parent_nodes;

                    $loop_scope->loop_parent_context->vars_in_scope[$var_id] =
                        $inner_context->vars_in_scope[$var_id];
                    $loop_scope->loop_parent_context->removeVarFromConflictingClauses($var_id);

                    continue;
                }

                if ($inner_context->vars_in_scope[$var_id]->getId() !== $type->getId()) {
                    $loop_scope->loop_parent_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                        $loop_scope->loop_parent_context->vars_in_scope[$var_id],
                        $inner_context->vars_in_scope[$var_id]
                    );

                    $loop_scope->loop_parent_context->removeVarFromConflictingClauses($var_id);
                } else {
                    $loop_scope->loop_parent_context->vars_in_scope[$var_id]->parent_nodes = array_merge(
                        $loop_scope->loop_parent_context->vars_in_scope[$var_id]->parent_nodes,
                        $inner_context->vars_in_scope[$var_id]->parent_nodes
                    );
                }
            }
        }

        if ($pre_conditions && $pre_condition_clauses && !ScopeAnalyzer::doesEverBreak($stmts)) {
            // if the loop contains an assertion and there are no break statements, we can negate that assertion
            // and apply it to the current context

            try {
                $negated_pre_condition_clauses = Algebra::negateFormula(array_merge(...$pre_condition_clauses));
            } catch (\Psalm\Exception\ComplicatedExpressionException $e) {
                $negated_pre_condition_clauses = [];
            }

            $negated_pre_condition_types = Algebra::getTruthsFromFormula($negated_pre_condition_clauses);

            if ($negated_pre_condition_types) {
                $changed_var_ids = [];

                $vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
                    $negated_pre_condition_types,
                    [],
                    $inner_context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_analyzer,
                    [],
                    true,
                    new CodeLocation($statements_analyzer->getSource(), $pre_conditions[0])
                );

                foreach ($changed_var_ids as $var_id => $_) {
                    if (isset($vars_in_scope_reconciled[$var_id])
                        && isset($loop_scope->loop_parent_context->vars_in_scope[$var_id])
                    ) {
                        $loop_scope->loop_parent_context->vars_in_scope[$var_id] = $vars_in_scope_reconciled[$var_id];
                    }

                    $loop_scope->loop_parent_context->removeVarFromConflictingClauses($var_id);
                }
            }
        }

        $loop_scope->loop_context->referenced_var_ids = array_merge(
            array_intersect_key(
                $inner_context->referenced_var_ids,
                $pre_outer_context->vars_in_scope
            ),
            $loop_scope->loop_context->referenced_var_ids
        );

        if ($always_enters_loop) {
            foreach ($inner_context->vars_in_scope as $var_id => $type) {
                // if there are break statements in the loop it's not certain
                // that the loop has finished executing, so the assertions at the end
                // the loop in the while conditional may not hold
                if (in_array(ScopeAnalyzer::ACTION_BREAK, $loop_scope->final_actions, true)
                    || in_array(ScopeAnalyzer::ACTION_CONTINUE, $loop_scope->final_actions, true)
                ) {
                    if (isset($loop_scope->possibly_defined_loop_parent_vars[$var_id])) {
                        $loop_scope->loop_parent_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $type,
                            $loop_scope->possibly_defined_loop_parent_vars[$var_id]
                        );
                    }
                } else {
                    $loop_scope->loop_parent_context->vars_in_scope[$var_id] = $type;
                }
            }
        }

        if ($inner_do_context) {
            $inner_context = $inner_do_context;
        }

        return null;
    }

    private static function updateLoopScopeContexts(
        LoopScope $loop_scope,
        Context $pre_outer_context
    ): void {
        $updated_loop_vars = [];

        if (!in_array(ScopeAnalyzer::ACTION_CONTINUE, $loop_scope->final_actions, true)) {
            $loop_scope->loop_context->vars_in_scope = $pre_outer_context->vars_in_scope;
        } else {
            if ($loop_scope->redefined_loop_vars !== null) {
                foreach ($loop_scope->redefined_loop_vars as $var => $type) {
                    $loop_scope->loop_context->vars_in_scope[$var] = $type;
                    $updated_loop_vars[$var] = true;
                }
            }

            if ($loop_scope->possibly_redefined_loop_vars) {
                foreach ($loop_scope->possibly_redefined_loop_vars as $var => $type) {
                    if ($loop_scope->loop_context->hasVariable($var)) {
                        if (!isset($updated_loop_vars[$var])) {
                            $loop_scope->loop_context->vars_in_scope[$var] = Type::combineUnionTypes(
                                $loop_scope->loop_context->vars_in_scope[$var],
                                $type
                            );
                        } else {
                            $loop_scope->loop_context->vars_in_scope[$var]->parent_nodes
                                += $type->parent_nodes;
                        }
                    }
                }
            }
        }

        // merge vars possibly in scope at the end of each loop
        $loop_scope->loop_context->vars_possibly_in_scope = array_merge(
            $loop_scope->loop_context->vars_possibly_in_scope,
            $loop_scope->vars_possibly_in_scope
        );
    }

    /**
     * @param  list<Clause>  $pre_condition_clauses
     *
     * @return list<string>
     */
    private static function applyPreConditionToLoopContext(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $pre_condition,
        array $pre_condition_clauses,
        Context $loop_context,
        Context $outer_context,
        bool $is_do
    ): array {
        $pre_referenced_var_ids = $loop_context->referenced_var_ids;
        $loop_context->referenced_var_ids = [];

        $loop_context->inside_conditional = true;

        $suppressed_issues = $statements_analyzer->getSuppressedIssues();

        if (!in_array('RedundantCondition', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['RedundantCondition']);
        }
        if (!in_array('RedundantConditionGivenDocblockType', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['RedundantConditionGivenDocblockType']);
        }
        if (!in_array('TypeDoesNotContainType', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['TypeDoesNotContainType']);
        }

        if (ExpressionAnalyzer::analyze($statements_analyzer, $pre_condition, $loop_context) === false) {
            return [];
        }

        $loop_context->inside_conditional = false;

        $new_referenced_var_ids = $loop_context->referenced_var_ids;
        $loop_context->referenced_var_ids = array_merge($pre_referenced_var_ids, $new_referenced_var_ids);

        $always_assigned_before_loop_body_vars = Context::getNewOrUpdatedVarIds($outer_context, $loop_context);

        $loop_context->clauses = Algebra::simplifyCNF(
            array_merge($outer_context->clauses, $pre_condition_clauses)
        );

        $active_while_types = [];

        $reconcilable_while_types = Algebra::getTruthsFromFormula(
            $loop_context->clauses,
            \spl_object_id($pre_condition),
            $new_referenced_var_ids
        );

        $changed_var_ids = [];

        if ($reconcilable_while_types) {
            $pre_condition_vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
                $reconcilable_while_types,
                $active_while_types,
                $loop_context->vars_in_scope,
                $changed_var_ids,
                $new_referenced_var_ids,
                $statements_analyzer,
                [],
                true,
                new CodeLocation($statements_analyzer->getSource(), $pre_condition)
            );

            $loop_context->vars_in_scope = $pre_condition_vars_in_scope_reconciled;
        }

        if (!in_array('RedundantCondition', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['RedundantCondition']);
        }
        if (!in_array('RedundantConditionGivenDocblockType', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['RedundantConditionGivenDocblockType']);
        }
        if (!in_array('TypeDoesNotContainType', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['TypeDoesNotContainType']);
        }

        if ($is_do) {
            return [];
        }

        foreach ($always_assigned_before_loop_body_vars as $var_id) {
            $loop_context->clauses = Context::filterClauses(
                $var_id,
                $loop_context->clauses,
                null,
                $statements_analyzer
            );
        }

        return $always_assigned_before_loop_body_vars;
    }

    /**
     * @param  array<string, array<string, bool>>   $assignment_map
     *
     */
    private static function getAssignmentMapDepth(string $first_var_id, array $assignment_map): int
    {
        $max_depth = 0;

        $assignment_var_ids = $assignment_map[$first_var_id];
        unset($assignment_map[$first_var_id]);

        foreach ($assignment_var_ids as $assignment_var_id => $_) {
            $depth = 1;

            if (isset($assignment_map[$assignment_var_id])) {
                $depth = 1 + self::getAssignmentMapDepth($assignment_var_id, $assignment_map);
            }

            if ($depth > $max_depth) {
                $max_depth = $depth;
            }
        }

        return $max_depth;
    }
}
