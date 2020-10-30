<?php
namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\AlgebraAnalyzer;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ContinueOutsideLoop;
use Psalm\Issue\ParadoxicalCondition;
use Psalm\IssueBuffer;
use Psalm\Internal\Scope\CaseScope;
use Psalm\Internal\Scope\SwitchScope;
use Psalm\Type;
use Psalm\Type\Algebra;
use Psalm\Type\Reconciler;
use function count;
use function in_array;
use function array_merge;
use function is_string;
use function substr;
use function array_intersect_key;
use function array_diff_key;

/**
 * @internal
 */
class SwitchCaseAnalyzer
{
    /**
     * @return null|false
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Stmt\Switch_ $stmt,
        ?string $switch_var_id,
        PhpParser\Node\Stmt\Case_ $case,
        Context $context,
        Context $original_context,
        string $case_exit_type,
        array $case_actions,
        bool $is_last,
        SwitchScope $switch_scope
    ): ?bool {
        // has a return/throw at end
        $has_ending_statements = $case_actions === [ScopeAnalyzer::ACTION_END];
        $has_leaving_statements = $has_ending_statements
            || (count($case_actions) && !in_array(ScopeAnalyzer::ACTION_NONE, $case_actions, true));

        $case_context = clone $original_context;

        if ($codebase->alter_code) {
            $case_context->branch_point = $case_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
        }

        $case_context->parent_context = $context;
        $case_scope = $case_context->case_scope = new CaseScope($case_context);

        $case_equality_expr = null;

        $old_node_data = $statements_analyzer->node_data;

        $fake_switch_condition = false;

        if ($switch_var_id && substr($switch_var_id, 0, 15) === '$__tmp_switch__') {
            $switch_condition = new PhpParser\Node\Expr\Variable(
                substr($switch_var_id, 1),
                $stmt->cond->getAttributes()
            );

            $fake_switch_condition = true;
        } else {
            $switch_condition = $stmt->cond;
        }

        if ($case->cond) {
            $was_inside_conditional = $case_context->inside_conditional;
            $case_context->inside_conditional = true;

            if (ExpressionAnalyzer::analyze($statements_analyzer, $case->cond, $case_context) === false) {
                /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
                $case_scope->parent_context = null;
                $case_context->case_scope = null;
                $case_context->parent_context = null;

                return false;
            }

            if (!$was_inside_conditional) {
                $case_context->inside_conditional = false;
            }

            $statements_analyzer->node_data = clone $statements_analyzer->node_data;

            $traverser = new PhpParser\NodeTraverser;
            $traverser->addVisitor(
                new \Psalm\Internal\PhpVisitor\ConditionCloningVisitor(
                    $statements_analyzer->node_data
                )
            );

            /** @var PhpParser\Node\Expr */
            $switch_condition = $traverser->traverse([$switch_condition])[0];

            if ($fake_switch_condition) {
                $statements_analyzer->node_data->setType(
                    $switch_condition,
                    $case_context->vars_in_scope[$switch_var_id] ?? Type::getMixed()
                );
            }

            if ($switch_condition instanceof PhpParser\Node\Expr\Variable
                && is_string($switch_condition->name)
                && isset($context->vars_in_scope['$' . $switch_condition->name])
            ) {
                $switch_var_type = $context->vars_in_scope['$' . $switch_condition->name];

                $type_statements = [];

                foreach ($switch_var_type->getAtomicTypes() as $type) {
                    if ($type instanceof Type\Atomic\TDependentGetClass) {
                        $type_statements[] = new PhpParser\Node\Expr\FuncCall(
                            new PhpParser\Node\Name(['get_class']),
                            [
                                new PhpParser\Node\Arg(
                                    new PhpParser\Node\Expr\Variable(substr($type->typeof, 1))
                                ),
                            ]
                        );
                    } elseif ($type instanceof Type\Atomic\TDependentGetType) {
                        $type_statements[] = new PhpParser\Node\Expr\FuncCall(
                            new PhpParser\Node\Name(['gettype']),
                            [
                                new PhpParser\Node\Arg(
                                    new PhpParser\Node\Expr\Variable(substr($type->typeof, 1))
                                ),
                            ]
                        );
                    } elseif ($type instanceof Type\Atomic\TDependentGetDebugType) {
                        $type_statements[] = new PhpParser\Node\Expr\FuncCall(
                            new PhpParser\Node\Name(['get_debug_type']),
                            [
                                new PhpParser\Node\Arg(
                                    new PhpParser\Node\Expr\Variable(substr($type->typeof, 1))
                                ),
                            ]
                        );
                    } else {
                        $type_statements = null;
                        break;
                    }
                }

                if ($type_statements && count($type_statements) === 1) {
                    $switch_condition = $type_statements[0];

                    if ($fake_switch_condition) {
                        $statements_analyzer->node_data->setType(
                            $switch_condition,
                            $case_context->vars_in_scope[$switch_var_id] ?? Type::getMixed()
                        );
                    }
                }
            }

            if (($switch_condition_type = $statements_analyzer->node_data->getType($switch_condition))
                && ($case_cond_type = $statements_analyzer->node_data->getType($case->cond))
                && (($switch_condition_type->isString() && $case_cond_type->isString())
                    || ($switch_condition_type->isInt() && $case_cond_type->isInt())
                    || ($switch_condition_type->isFloat() && $case_cond_type->isFloat())
                )
            ) {
                $case_equality_expr = new PhpParser\Node\Expr\BinaryOp\Identical(
                    $switch_condition,
                    $case->cond,
                    $case->cond->getAttributes()
                );
            } else {
                $case_equality_expr = new PhpParser\Node\Expr\BinaryOp\Equal(
                    $switch_condition,
                    $case->cond,
                    $case->cond->getAttributes()
                );
            }
        }

        $continue_case_equality_expr = false;

        if ($case->stmts) {
            $case_stmts = array_merge($switch_scope->leftover_statements, $case->stmts);
        } else {
            $continue_case_equality_expr = count($switch_scope->leftover_statements) === 1;
            $case_stmts = $switch_scope->leftover_statements;
        }

        if (!$has_leaving_statements && !$is_last) {
            if (!$case_equality_expr) {
                $case_equality_expr = new PhpParser\Node\Expr\FuncCall(
                    new PhpParser\Node\Name\FullyQualified(['rand']),
                    [
                        new PhpParser\Node\Arg(new PhpParser\Node\Scalar\LNumber(0)),
                        new PhpParser\Node\Arg(new PhpParser\Node\Scalar\LNumber(1)),
                    ],
                    $case->getAttributes()
                );
            }

            $switch_scope->leftover_case_equality_expr = $switch_scope->leftover_case_equality_expr
                ? new PhpParser\Node\Expr\BinaryOp\BooleanOr(
                    $switch_scope->leftover_case_equality_expr,
                    $case_equality_expr,
                    $case->cond ? $case->cond->getAttributes() : $case->getAttributes()
                )
                : $case_equality_expr;

            if ($continue_case_equality_expr
                && $switch_scope->leftover_statements[0] instanceof PhpParser\Node\Stmt\If_
            ) {
                $case_if_stmt = $switch_scope->leftover_statements[0];
                $case_if_stmt->cond = $switch_scope->leftover_case_equality_expr;
            } else {
                $case_if_stmt = new PhpParser\Node\Stmt\If_(
                    $switch_scope->leftover_case_equality_expr,
                    ['stmts' => $case_stmts]
                );

                $switch_scope->leftover_statements = [$case_if_stmt];
            }

            /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
            $case_scope->parent_context = null;
            $case_context->case_scope = null;
            $case_context->parent_context = null;

            $statements_analyzer->node_data = $old_node_data;

            return null;
        }

        if ($switch_scope->leftover_case_equality_expr) {
            $case_or_default_equality_expr = $case_equality_expr;

            if (!$case_or_default_equality_expr) {
                $case_or_default_equality_expr = new PhpParser\Node\Expr\FuncCall(
                    new PhpParser\Node\Name\FullyQualified(['rand']),
                    [
                        new PhpParser\Node\Arg(new PhpParser\Node\Scalar\LNumber(0)),
                        new PhpParser\Node\Arg(new PhpParser\Node\Scalar\LNumber(1)),
                    ],
                    $case->getAttributes()
                );
            }

            $case_equality_expr = new PhpParser\Node\Expr\BinaryOp\BooleanOr(
                $switch_scope->leftover_case_equality_expr,
                $case_or_default_equality_expr,
                $case_or_default_equality_expr->getAttributes()
            );
        }

        if ($case_equality_expr
            && $switch_condition instanceof PhpParser\Node\Expr\Variable
            && is_string($switch_condition->name)
            && isset($context->vars_in_scope['$' . $switch_condition->name])
        ) {
            $new_case_equality_expr = self::simplifyCaseEqualityExpression(
                $case_equality_expr,
                $switch_condition
            );

            if ($new_case_equality_expr) {
                ExpressionAnalyzer::analyze(
                    $statements_analyzer,
                    $new_case_equality_expr->args[1]->value,
                    $case_context
                );

                $case_equality_expr = $new_case_equality_expr;
            }
        }

        $case_context->break_types[] = 'switch';

        $switch_scope->leftover_statements = [];
        $switch_scope->leftover_case_equality_expr = null;

        $case_clauses = [];

        if ($case_equality_expr) {
            $case_equality_expr_id = \spl_object_id($case_equality_expr);
            $case_clauses = Algebra::getFormula(
                $case_equality_expr_id,
                $case_equality_expr_id,
                $case_equality_expr,
                $context->self,
                $statements_analyzer,
                $codebase,
                false,
                false
            );
        }

        if ($switch_scope->negated_clauses && count($switch_scope->negated_clauses) < 50) {
            $entry_clauses = Algebra::simplifyCNF(
                array_merge(
                    $original_context->clauses,
                    $switch_scope->negated_clauses
                )
            );
        } else {
            $entry_clauses = $original_context->clauses;
        }

        if ($case_clauses && $case->cond) {
            // this will see whether any of the clauses in set A conflict with the clauses in set B
            AlgebraAnalyzer::checkForParadox(
                $entry_clauses,
                $case_clauses,
                $statements_analyzer,
                $case->cond,
                []
            );

            if (count($entry_clauses) + count($case_clauses) < 50) {
                $case_context->clauses = Algebra::simplifyCNF(array_merge($entry_clauses, $case_clauses));
            } else {
                $case_context->clauses = array_merge($entry_clauses, $case_clauses);
            }
        } else {
            $case_context->clauses = $entry_clauses;
        }

        $reconcilable_if_types = Algebra::getTruthsFromFormula($case_context->clauses);

        // if the if has an || in the conditional, we cannot easily reason about it
        if ($reconcilable_if_types) {
            $changed_var_ids = [];

            $suppressed_issues = $statements_analyzer->getSuppressedIssues();

            if (!in_array('RedundantCondition', $suppressed_issues, true)) {
                $statements_analyzer->addSuppressedIssues(['RedundantCondition']);
            }

            if (!in_array('RedundantConditionGivenDocblockType', $suppressed_issues, true)) {
                $statements_analyzer->addSuppressedIssues(['RedundantConditionGivenDocblockType']);
            }

            $case_vars_in_scope_reconciled =
                Reconciler::reconcileKeyedTypes(
                    $reconcilable_if_types,
                    [],
                    $case_context->vars_in_scope,
                    $changed_var_ids,
                    $case->cond && $switch_var_id ? [$switch_var_id => true] : [],
                    $statements_analyzer,
                    [],
                    $case_context->inside_loop,
                    new CodeLocation(
                        $statements_analyzer->getSource(),
                        $case->cond ? $case->cond : $case,
                        $context->include_location
                    )
                );

            if (!in_array('RedundantCondition', $suppressed_issues, true)) {
                $statements_analyzer->removeSuppressedIssues(['RedundantCondition']);
            }

            if (!in_array('RedundantConditionGivenDocblockType', $suppressed_issues, true)) {
                $statements_analyzer->removeSuppressedIssues(['RedundantConditionGivenDocblockType']);
            }

            $case_context->vars_in_scope = $case_vars_in_scope_reconciled;
            foreach ($reconcilable_if_types as $var_id => $_) {
                $case_context->vars_possibly_in_scope[$var_id] = true;
            }

            if ($changed_var_ids) {
                $case_context->clauses = Context::removeReconciledClauses($case_context->clauses, $changed_var_ids)[0];
            }
        }

        if ($case_clauses && $case_equality_expr) {
            try {
                $negated_case_clauses = Algebra::negateFormula($case_clauses);
            } catch (\Psalm\Exception\ComplicatedExpressionException $e) {
                $case_equality_expr_id = \spl_object_id($case_equality_expr);

                try {
                    $negated_case_clauses = Algebra::getFormula(
                        $case_equality_expr_id,
                        $case_equality_expr_id,
                        new PhpParser\Node\Expr\BooleanNot($case_equality_expr),
                        $context->self,
                        $statements_analyzer,
                        $codebase,
                        false,
                        false
                    );
                } catch (\Psalm\Exception\ComplicatedExpressionException $e) {
                    $negated_case_clauses = [];
                }
            }

            $switch_scope->negated_clauses = array_merge(
                $switch_scope->negated_clauses,
                $negated_case_clauses
            );
        }

        $pre_possibly_assigned_var_ids = $case_context->possibly_assigned_var_ids;
        $case_context->possibly_assigned_var_ids = [];

        $pre_assigned_var_ids = $case_context->assigned_var_ids;
        $case_context->assigned_var_ids = [];

        $statements_analyzer->analyze($case_stmts, $case_context);

        $traverser = new PhpParser\NodeTraverser;
        $traverser->addVisitor(
            new \Psalm\Internal\PhpVisitor\TypeMappingVisitor(
                $statements_analyzer->node_data,
                $old_node_data
            )
        );

        $traverser->traverse([$case]);

        $statements_analyzer->node_data = $old_node_data;

        /** @var array<string, bool> */
        $new_case_assigned_var_ids = $case_context->assigned_var_ids;
        $case_context->assigned_var_ids = $pre_assigned_var_ids + $new_case_assigned_var_ids;

        /** @var array<string, bool> */
        $new_case_possibly_assigned_var_ids = $case_context->possibly_assigned_var_ids;
        $case_context->possibly_assigned_var_ids =
            $pre_possibly_assigned_var_ids + $new_case_possibly_assigned_var_ids;

        $context->referenced_var_ids = array_merge(
            $context->referenced_var_ids,
            $case_context->referenced_var_ids
        );

        if ($case_exit_type !== 'return_throw') {
            if (self::handleNonReturningCase(
                $statements_analyzer,
                $switch_var_id,
                $case,
                $context,
                $case_context,
                $original_context,
                $case_exit_type,
                $switch_scope
            ) === false) {
                /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
                $case_scope->parent_context = null;
                $case_context->case_scope = null;
                $case_context->parent_context = null;

                return false;
            }
        }

        // augment the information with data from break statements
        if ($case_scope->break_vars !== null) {
            if ($switch_scope->possibly_redefined_vars === null) {
                $switch_scope->possibly_redefined_vars = array_intersect_key(
                    $case_scope->break_vars,
                    $context->vars_in_scope
                );
            } else {
                foreach ($case_scope->break_vars as $var_id => $type) {
                    if (isset($context->vars_in_scope[$var_id])) {
                        if (!isset($switch_scope->possibly_redefined_vars[$var_id])) {
                            $switch_scope->possibly_redefined_vars[$var_id] = clone $type;
                        } else {
                            $switch_scope->possibly_redefined_vars[$var_id] = Type::combineUnionTypes(
                                clone $type,
                                $switch_scope->possibly_redefined_vars[$var_id]
                            );
                        }
                    }
                }
            }

            if ($switch_scope->new_vars_in_scope !== null) {
                foreach ($switch_scope->new_vars_in_scope as $var_id => $type) {
                    if (isset($case_scope->break_vars[$var_id])) {
                        if (!isset($case_context->vars_in_scope[$var_id])) {
                            unset($switch_scope->new_vars_in_scope[$var_id]);
                        } else {
                            $switch_scope->new_vars_in_scope[$var_id] = Type::combineUnionTypes(
                                clone $case_scope->break_vars[$var_id],
                                $type
                            );
                        }
                    } else {
                        unset($switch_scope->new_vars_in_scope[$var_id]);
                    }
                }
            }

            if ($switch_scope->redefined_vars !== null) {
                foreach ($switch_scope->redefined_vars as $var_id => $type) {
                    if (isset($case_scope->break_vars[$var_id])) {
                        $switch_scope->redefined_vars[$var_id] = Type::combineUnionTypes(
                            clone $case_scope->break_vars[$var_id],
                            $type
                        );
                    } else {
                        unset($switch_scope->redefined_vars[$var_id]);
                    }
                }
            }
        }

        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $case_scope->parent_context = null;
        $case_context->case_scope = null;
        $case_context->parent_context = null;

        return null;
    }

    /**
     * @param array<string, bool> $new_case_assigned_var_ids
     * @param array<string, bool> $new_case_possibly_assigned_var_ids
     * @return null|false
     */
    private static function handleNonReturningCase(
        StatementsAnalyzer $statements_analyzer,
        ?string $switch_var_id,
        PhpParser\Node\Stmt\Case_ $case,
        Context $context,
        Context $case_context,
        Context $original_context,
        string $case_exit_type,
        SwitchScope $switch_scope
    ): ?bool {
        if (!$case->cond
            && $switch_var_id
            && isset($case_context->vars_in_scope[$switch_var_id])
            && $case_context->vars_in_scope[$switch_var_id]->isEmpty()
        ) {
            if (IssueBuffer::accepts(
                new ParadoxicalCondition(
                    'All possible case statements have been met, default is impossible here',
                    new CodeLocation($statements_analyzer->getSource(), $case)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }
        }

        // if we're leaving this block, add vars to outer for loop scope
        if ($case_exit_type === 'continue') {
            if (!$context->loop_scope) {
                if (IssueBuffer::accepts(
                    new ContinueOutsideLoop(
                        'Continue called when not in loop',
                        new CodeLocation($statements_analyzer->getSource(), $case)
                    )
                )) {
                    return false;
                }
            }
        } else {
            $case_redefined_vars = $case_context->getRedefinedVars($original_context->vars_in_scope);

            if ($switch_scope->possibly_redefined_vars === null) {
                $switch_scope->possibly_redefined_vars = $case_redefined_vars;
            } else {
                foreach ($case_redefined_vars as $var_id => $type) {
                    if (!isset($switch_scope->possibly_redefined_vars[$var_id])) {
                        $switch_scope->possibly_redefined_vars[$var_id] = clone $type;
                    } else {
                        $switch_scope->possibly_redefined_vars[$var_id] = Type::combineUnionTypes(
                            clone $type,
                            $switch_scope->possibly_redefined_vars[$var_id]
                        );
                    }
                }
            }

            if ($switch_scope->redefined_vars === null) {
                $switch_scope->redefined_vars = $case_redefined_vars;
            } else {
                foreach ($switch_scope->redefined_vars as $var_id => $type) {
                    if (!isset($case_redefined_vars[$var_id])) {
                        unset($switch_scope->redefined_vars[$var_id]);
                    } else {
                        $switch_scope->redefined_vars[$var_id] = Type::combineUnionTypes(
                            $type,
                            clone $case_redefined_vars[$var_id]
                        );
                    }
                }
            }

            $context_new_vars = array_diff_key($case_context->vars_in_scope, $context->vars_in_scope);

            if ($switch_scope->new_vars_in_scope === null) {
                $switch_scope->new_vars_in_scope = $context_new_vars;
                $switch_scope->new_vars_possibly_in_scope = array_diff_key(
                    $case_context->vars_possibly_in_scope,
                    $context->vars_possibly_in_scope
                );
            } else {
                foreach ($switch_scope->new_vars_in_scope as $new_var => $type) {
                    if (!$case_context->hasVariable($new_var)) {
                        unset($switch_scope->new_vars_in_scope[$new_var]);
                    } else {
                        $switch_scope->new_vars_in_scope[$new_var] =
                            Type::combineUnionTypes(clone $case_context->vars_in_scope[$new_var], $type);
                    }
                }

                $switch_scope->new_vars_possibly_in_scope = array_merge(
                    array_diff_key(
                        $case_context->vars_possibly_in_scope,
                        $context->vars_possibly_in_scope
                    ),
                    $switch_scope->new_vars_possibly_in_scope
                );
            }
        }

        if ($context->collect_exceptions) {
            $context->mergeExceptions($case_context);
        }

        return null;
    }

    private static function simplifyCaseEqualityExpression(
        PhpParser\Node\Expr $case_equality_expr,
        PhpParser\Node\Expr\Variable $var
    ) : ?PhpParser\Node\Expr\FuncCall {
        if ($case_equality_expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            $nested_or_options = self::getOptionsFromNestedOr($case_equality_expr, $var);

            if ($nested_or_options) {
                return new PhpParser\Node\Expr\FuncCall(
                    new PhpParser\Node\Name\FullyQualified(['in_array']),
                    [
                        new PhpParser\Node\Arg(
                            $var
                        ),
                        new PhpParser\Node\Arg(
                            new PhpParser\Node\Expr\Array_(
                                $nested_or_options
                            )
                        ),
                        new PhpParser\Node\Arg(
                            new PhpParser\Node\Expr\ConstFetch(
                                new PhpParser\Node\Name\FullyQualified(['true'])
                            )
                        ),
                    ]
                );
            }
        }

        return null;
    }

    /**
     * @param array<PhpParser\Node\Expr\ArrayItem> $in_array_values
     * @return ?array<PhpParser\Node\Expr\ArrayItem>
     */
    private static function getOptionsFromNestedOr(
        PhpParser\Node\Expr $case_equality_expr,
        PhpParser\Node\Expr\Variable $var,
        array $in_array_values = []
    ) : ?array {
        if ($case_equality_expr instanceof PhpParser\Node\Expr\BinaryOp\Identical
            && $case_equality_expr->left instanceof PhpParser\Node\Expr\Variable
            && $case_equality_expr->left->name === $var->name
        ) {
            $in_array_values[] = new PhpParser\Node\Expr\ArrayItem(
                $case_equality_expr->right
            );

            return $in_array_values;
        }

        if (!$case_equality_expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            return null;
        }

        if (!$case_equality_expr->right instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || !$case_equality_expr->right->left instanceof PhpParser\Node\Expr\Variable
            || $case_equality_expr->right->left->name !== $var->name
        ) {
            return null;
        }

        $in_array_values[] = new PhpParser\Node\Expr\ArrayItem($case_equality_expr->right->right);

        return self::getOptionsFromNestedOr(
            $case_equality_expr->left,
            $var,
            $in_array_values
        );
    }
}
