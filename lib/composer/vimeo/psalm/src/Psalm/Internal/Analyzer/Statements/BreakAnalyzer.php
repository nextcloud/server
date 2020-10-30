<?php
namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Context;
use Psalm\Type;

class BreakAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Break_ $stmt,
        Context $context
    ) : void {
        $loop_scope = $context->loop_scope;

        $leaving_switch = true;

        if ($loop_scope) {
            if ($context->break_types
                && \end($context->break_types) === 'switch'
                && (!$stmt->num
                    || !$stmt->num instanceof PhpParser\Node\Scalar\LNumber
                    || $stmt->num->value < 2
                )
            ) {
                $loop_scope->final_actions[] = ScopeAnalyzer::ACTION_LEAVE_SWITCH;
            } else {
                $leaving_switch = false;

                $loop_scope->final_actions[] = ScopeAnalyzer::ACTION_BREAK;
            }

            $redefined_vars = $context->getRedefinedVars($loop_scope->loop_parent_context->vars_in_scope);

            if ($loop_scope->possibly_redefined_loop_parent_vars === null) {
                $loop_scope->possibly_redefined_loop_parent_vars = $redefined_vars;
            } else {
                foreach ($redefined_vars as $var => $type) {
                    if ($type->hasMixed()) {
                        if (isset($loop_scope->possibly_redefined_loop_parent_vars[$var])) {
                            $type->parent_nodes
                                += $loop_scope->possibly_redefined_loop_parent_vars[$var]->parent_nodes;
                        }

                        $loop_scope->possibly_redefined_loop_parent_vars[$var] = $type;
                    } elseif (isset($loop_scope->possibly_redefined_loop_parent_vars[$var])) {
                        $loop_scope->possibly_redefined_loop_parent_vars[$var] = Type::combineUnionTypes(
                            $type,
                            $loop_scope->possibly_redefined_loop_parent_vars[$var]
                        );
                    } else {
                        $loop_scope->possibly_redefined_loop_parent_vars[$var] = $type;
                    }
                }
            }

            if ($loop_scope->iteration_count === 0) {
                foreach ($context->vars_in_scope as $var_id => $type) {
                    if (!isset($loop_scope->loop_parent_context->vars_in_scope[$var_id])) {
                        if (isset($loop_scope->possibly_defined_loop_parent_vars[$var_id])) {
                            $loop_scope->possibly_defined_loop_parent_vars[$var_id] = Type::combineUnionTypes(
                                $type,
                                $loop_scope->possibly_defined_loop_parent_vars[$var_id]
                            );
                        } else {
                            $loop_scope->possibly_defined_loop_parent_vars[$var_id] = $type;
                        }
                    }
                }
            }

            if ($context->finally_scope) {
                foreach ($context->vars_in_scope as $var_id => $type) {
                    if (isset($context->finally_scope->vars_in_scope[$var_id])) {
                        if ($context->finally_scope->vars_in_scope[$var_id] !== $type) {
                            $context->finally_scope->vars_in_scope[$var_id] = Type::combineUnionTypes(
                                $context->finally_scope->vars_in_scope[$var_id],
                                $type,
                                $statements_analyzer->getCodebase()
                            );
                        }
                    } else {
                        $context->finally_scope->vars_in_scope[$var_id] = $type;
                    }
                }
            }
        }

        $case_scope = $context->case_scope;
        if ($case_scope && $leaving_switch) {
            foreach ($context->vars_in_scope as $var_id => $type) {
                if ($case_scope->parent_context !== $context) {
                    if ($case_scope->break_vars === null) {
                        $case_scope->break_vars = [];
                    }

                    if (isset($case_scope->break_vars[$var_id])) {
                        $case_scope->break_vars[$var_id] = Type::combineUnionTypes(
                            $type,
                            $case_scope->break_vars[$var_id]
                        );
                    } else {
                        $case_scope->break_vars[$var_id] = $type;
                    }
                }
            }
        }

        $context->has_returned = true;
    }
}
