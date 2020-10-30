<?php
namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ContinueOutsideLoop;
use Psalm\IssueBuffer;
use Psalm\Type;

class ContinueAnalyzer
{
    /**
     * @return false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Continue_ $stmt,
        Context $context
    ): ?bool {
        $loop_scope = $context->loop_scope;

        if ($loop_scope === null) {
            if (!$context->break_types) {
                if (IssueBuffer::accepts(
                    new ContinueOutsideLoop(
                        'Continue call outside loop context',
                        new CodeLocation($statements_analyzer, $stmt)
                    ),
                    $statements_analyzer->getSource()->getSuppressedIssues()
                )) {
                    return false;
                }
            }
        } else {
            if ($context->break_types
                && \end($context->break_types) === 'switch'
                && (!$stmt->num
                    || !$stmt->num instanceof PhpParser\Node\Scalar\LNumber
                    || $stmt->num->value < 2
                )
            ) {
                $loop_scope->final_actions[] = ScopeAnalyzer::ACTION_LEAVE_SWITCH;
            } else {
                $loop_scope->final_actions[] = ScopeAnalyzer::ACTION_CONTINUE;
            }

            $redefined_vars = $context->getRedefinedVars($loop_scope->loop_parent_context->vars_in_scope);

            if ($loop_scope->redefined_loop_vars === null) {
                $loop_scope->redefined_loop_vars = $redefined_vars;
            } else {
                foreach ($loop_scope->redefined_loop_vars as $redefined_var => $type) {
                    if (!isset($redefined_vars[$redefined_var])) {
                        unset($loop_scope->redefined_loop_vars[$redefined_var]);
                    } else {
                        $loop_scope->redefined_loop_vars[$redefined_var] = Type::combineUnionTypes(
                            $redefined_vars[$redefined_var],
                            $type
                        );
                    }
                }
            }

            foreach ($redefined_vars as $var => $type) {
                if ($type->hasMixed()) {
                    if (isset($loop_scope->possibly_redefined_loop_vars[$var])) {
                        $type->parent_nodes += $loop_scope->possibly_redefined_loop_vars[$var]->parent_nodes;
                    }

                    $loop_scope->possibly_redefined_loop_vars[$var] = $type;
                } elseif (isset($loop_scope->possibly_redefined_loop_vars[$var])) {
                    $loop_scope->possibly_redefined_loop_vars[$var] = Type::combineUnionTypes(
                        $type,
                        $loop_scope->possibly_redefined_loop_vars[$var]
                    );
                } else {
                    $loop_scope->possibly_redefined_loop_vars[$var] = $type;
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

        $context->has_returned = true;

        return null;
    }
}
