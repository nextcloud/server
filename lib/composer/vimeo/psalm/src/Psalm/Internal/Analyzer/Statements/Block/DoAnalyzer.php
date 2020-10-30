<?php
namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Context;
use Psalm\Internal\Scope\LoopScope;
use Psalm\Type;
use Psalm\Type\Algebra;
use function in_array;
use function array_values;
use function array_filter;
use function array_keys;
use function preg_match;
use function preg_quote;
use function array_merge;

/**
 * @internal
 */
class DoAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Do_ $stmt,
        Context $context
    ): void {
        $do_context = clone $context;
        $do_context->break_types[] = 'loop';

        $codebase = $statements_analyzer->getCodebase();

        if ($codebase->alter_code) {
            $do_context->branch_point = $do_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
        }

        $loop_scope = new LoopScope($do_context, $context);
        $loop_scope->protected_var_ids = $context->protected_var_ids;

        self::analyzeDoNaively($statements_analyzer, $stmt, $do_context, $loop_scope);

        $mixed_var_ids = [];

        foreach ($do_context->vars_in_scope as $var_id => $type) {
            if ($type->hasMixed()) {
                $mixed_var_ids[] = $var_id;
            }
        }

        $cond_id = \spl_object_id($stmt->cond);

        $while_clauses = Algebra::getFormula(
            $cond_id,
            $cond_id,
            $stmt->cond,
            $context->self,
            $statements_analyzer,
            $codebase
        );

        $while_clauses = array_values(
            array_filter(
                $while_clauses,
                function (Clause $c) use ($mixed_var_ids): bool {
                    $keys = array_keys($c->possibilities);

                    $mixed_var_ids = \array_diff($mixed_var_ids, $keys);

                    foreach ($keys as $key) {
                        foreach ($mixed_var_ids as $mixed_var_id) {
                            if (preg_match('/^' . preg_quote($mixed_var_id, '/') . '(\[|-)/', $key)) {
                                return false;
                            }
                        }
                    }

                    return true;
                }
            )
        );

        if (!$while_clauses) {
            $while_clauses = [new Clause([], $cond_id, $cond_id, true)];
        }

        LoopAnalyzer::analyze(
            $statements_analyzer,
            $stmt->stmts,
            [$stmt->cond],
            [],
            $loop_scope,
            $inner_loop_context,
            true,
            true
        );

        // because it's a do {} while, inner loop vars belong to the main context
        if (!$inner_loop_context) {
            throw new \UnexpectedValueException('Should never be null');
        }

        $negated_while_clauses = Algebra::negateFormula($while_clauses);

        $negated_while_types = Algebra::getTruthsFromFormula(
            Algebra::simplifyCNF(
                array_merge($context->clauses, $negated_while_clauses)
            )
        );

        //var_dump($do_context->vars_in_scope);

        if ($negated_while_types) {
            $changed_var_ids = [];

            $inner_loop_context->vars_in_scope =
                Type\Reconciler::reconcileKeyedTypes(
                    $negated_while_types,
                    [],
                    $inner_loop_context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_analyzer,
                    [],
                    true,
                    new \Psalm\CodeLocation($statements_analyzer->getSource(), $stmt->cond)
                );
        }

        foreach ($inner_loop_context->vars_in_scope as $var_id => $type) {
            // if there are break statements in the loop it's not certain
            // that the loop has finished executing, so the assertions at the end
            // the loop in the while conditional may not hold
            if (in_array(ScopeAnalyzer::ACTION_BREAK, $loop_scope->final_actions, true)) {
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

        $do_context->loop_scope = null;

        $context->vars_possibly_in_scope = array_merge(
            $context->vars_possibly_in_scope,
            $do_context->vars_possibly_in_scope
        );

        $context->referenced_var_ids = array_merge(
            $context->referenced_var_ids,
            $do_context->referenced_var_ids
        );

        if ($context->collect_exceptions) {
            $context->mergeExceptions($inner_loop_context);
        }
    }

    private static function analyzeDoNaively(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Do_ $stmt,
        Context $context,
        LoopScope $loop_scope
    ) : void {
        $do_context = clone $context;

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

        $do_context->loop_scope = $loop_scope;

        $statements_analyzer->analyze($stmt->stmts, $do_context);

        if (!in_array('RedundantCondition', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['RedundantCondition']);
        }
        if (!in_array('RedundantConditionGivenDocblockType', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['RedundantConditionGivenDocblockType']);
        }
        if (!in_array('TypeDoesNotContainType', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['TypeDoesNotContainType']);
        }
    }
}
