<?php
namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Context;
use Psalm\Internal\Scope\LoopScope;
use Psalm\Type;
use function array_merge;
use function in_array;
use function array_intersect_key;

/**
 * @internal
 */
class ForAnalyzer
{
    /**
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\For_ $stmt,
        Context $context
    ): ?bool {
        $pre_assigned_var_ids = $context->assigned_var_ids;
        $context->assigned_var_ids = [];

        $init_var_types = [];

        foreach ($stmt->init as $init) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $init, $context) === false) {
                return false;
            }

            if ($init instanceof PhpParser\Node\Expr\Assign
                && $init->var instanceof PhpParser\Node\Expr\Variable
                && \is_string($init->var->name)
                && ($init_var_type = $statements_analyzer->node_data->getType($init->expr))
            ) {
                $init_var_types[$init->var->name] = $init_var_type;
            }
        }

        $assigned_var_ids = $context->assigned_var_ids;

        $context->assigned_var_ids = array_merge(
            $pre_assigned_var_ids,
            $assigned_var_ids
        );

        $while_true = !$stmt->cond && !$stmt->init && !$stmt->loop;

        $pre_context = null;

        if ($while_true) {
            $pre_context = clone $context;
        }

        $for_context = clone $context;

        $for_context->inside_loop = true;
        $for_context->break_types[] = 'loop';

        $codebase = $statements_analyzer->getCodebase();

        if ($codebase->alter_code) {
            $for_context->branch_point = $for_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
        }

        $loop_scope = new LoopScope($for_context, $context);

        $loop_scope->protected_var_ids = array_merge(
            $assigned_var_ids,
            $context->protected_var_ids
        );

        LoopAnalyzer::analyze(
            $statements_analyzer,
            $stmt->stmts,
            $stmt->cond,
            $stmt->loop,
            $loop_scope,
            $inner_loop_context
        );

        if (!$inner_loop_context) {
            throw new \UnexpectedValueException('There should be an inner loop context');
        }

        $always_enters_loop = false;

        foreach ($stmt->cond as $cond) {
            if ($cond_type = $statements_analyzer->node_data->getType($cond)) {
                foreach ($cond_type->getAtomicTypes() as $iterator_type) {
                    $always_enters_loop = $iterator_type instanceof Type\Atomic\TTrue;

                    break;
                }
            }

            if (\count($stmt->init) === 1
                && \count($stmt->cond) === 1
                && $cond instanceof PhpParser\Node\Expr\BinaryOp
                && $cond->right instanceof PhpParser\Node\Scalar\LNumber
                && $cond->left instanceof PhpParser\Node\Expr\Variable
                && \is_string($cond->left->name)
                && isset($init_var_types[$cond->left->name])
                && $init_var_types[$cond->left->name]->isSingleIntLiteral()
            ) {
                $init_value = $init_var_types[$cond->left->name]->getSingleIntLiteral()->value;
                $cond_value = $cond->right->value;

                if ($cond instanceof PhpParser\Node\Expr\BinaryOp\Smaller && $init_value < $cond_value) {
                    $always_enters_loop = true;
                    break;
                }

                if ($cond instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual && $init_value <= $cond_value) {
                    $always_enters_loop = true;
                    break;
                }

                if ($cond instanceof PhpParser\Node\Expr\BinaryOp\Greater && $init_value > $cond_value) {
                    $always_enters_loop = true;
                    break;
                }

                if ($cond instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual && $init_value >= $cond_value) {
                    $always_enters_loop = true;
                    break;
                }
            }
        }

        if ($while_true) {
            $always_enters_loop = true;
        }

        $can_leave_loop = !$while_true
            || in_array(ScopeAnalyzer::ACTION_BREAK, $loop_scope->final_actions, true);

        if ($always_enters_loop && $can_leave_loop) {
            foreach ($inner_loop_context->vars_in_scope as $var_id => $type) {
                // if there are break statements in the loop it's not certain
                // that the loop has finished executing
                if (in_array(ScopeAnalyzer::ACTION_BREAK, $loop_scope->final_actions, true)
                    || in_array(ScopeAnalyzer::ACTION_CONTINUE, $loop_scope->final_actions, true)
                ) {
                    if (isset($loop_scope->possibly_defined_loop_parent_vars[$var_id])) {
                        $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $type,
                            $loop_scope->possibly_defined_loop_parent_vars[$var_id]
                        );
                    }
                } else {
                    $context->vars_in_scope[$var_id] = $type;
                }
            }
        }

        $for_context->loop_scope = null;

        if ($can_leave_loop) {
            $context->vars_possibly_in_scope = array_merge(
                $context->vars_possibly_in_scope,
                $for_context->vars_possibly_in_scope
            );
        } elseif ($pre_context) {
            $context->vars_possibly_in_scope = $pre_context->vars_possibly_in_scope;
        }

        $context->referenced_var_ids = array_intersect_key(
            $for_context->referenced_var_ids,
            $context->referenced_var_ids
        );

        if ($context->collect_exceptions) {
            $context->mergeExceptions($for_context);
        }

        return null;
    }
}
