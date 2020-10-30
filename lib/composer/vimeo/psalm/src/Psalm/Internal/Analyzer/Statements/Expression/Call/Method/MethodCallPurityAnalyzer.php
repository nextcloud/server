<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use PhpParser;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\ImpureMethodCall;
use Psalm\IssueBuffer;
use Psalm\Type;

class MethodCallPurityAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\MethodCall $stmt,
        ?string $lhs_var_id,
        ?string $cased_method_id,
        MethodIdentifier $method_id,
        \Psalm\Storage\MethodStorage $method_storage,
        \Psalm\Storage\ClassLikeStorage $class_storage,
        Context $context,
        \Psalm\Config $config
    ) : bool {
        $can_memoize = false;

        $method_pure_compatible = $method_storage->external_mutation_free
            && $statements_analyzer->node_data->isPureCompatible($stmt->var);

        if ($context->pure
            && !$method_storage->mutation_free
            && !$method_pure_compatible
        ) {
            if (IssueBuffer::accepts(
                new ImpureMethodCall(
                    'Cannot call a non-mutation-free method '
                        . $cased_method_id . ' from a pure context',
                    new CodeLocation($statements_analyzer, $stmt->name)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        } elseif ($context->mutation_free
            && !$method_storage->mutation_free
            && !$method_pure_compatible
        ) {
            if (IssueBuffer::accepts(
                new ImpureMethodCall(
                    'Cannot call an possibly-mutating method '
                        . $cased_method_id . ' from a mutation-free context',
                    new CodeLocation($statements_analyzer, $stmt->name)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        } elseif ($context->external_mutation_free
            && !$method_storage->mutation_free
            && $method_id->fq_class_name !== $context->self
            && !$method_pure_compatible
        ) {
            if (IssueBuffer::accepts(
                new ImpureMethodCall(
                    'Cannot call an possibly-mutating method '
                        . $cased_method_id . ' from a mutation-free context',
                    new CodeLocation($statements_analyzer, $stmt->name)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        } elseif (($method_storage->mutation_free
                || ($method_storage->external_mutation_free
                    && (isset($stmt->var->external_mutation_free) || isset($stmt->var->pure))))
            && !$context->inside_unset
        ) {
            if ($method_storage->mutation_free
                && (!$method_storage->mutation_free_inferred
                    || $method_storage->final)
            ) {
                if ($context->inside_conditional
                    && !$method_storage->assertions
                    && !$method_storage->if_true_assertions
                ) {
                    /** @psalm-suppress UndefinedPropertyAssignment */
                    $stmt->pure = true;
                }

                $can_memoize = true;
            }

            if ($codebase->find_unused_variables
                && !$context->inside_conditional
                && !$context->inside_use
            ) {
                if (!$context->inside_assignment && !$context->inside_call) {
                    if (IssueBuffer::accepts(
                        new \Psalm\Issue\UnusedMethodCall(
                            'The call to ' . $cased_method_id . ' is not used',
                            new CodeLocation($statements_analyzer, $stmt->name),
                            (string) $method_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } elseif (!$method_storage->mutation_free_inferred) {
                    /** @psalm-suppress UndefinedPropertyAssignment */
                    $stmt->pure = true;
                }
            }
        }

        if ($statements_analyzer->getSource() instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
            && $statements_analyzer->getSource()->track_mutations
            && !$method_storage->mutation_free
            && !$method_pure_compatible
        ) {
            if (!$method_storage->mutation_free) {
                $statements_analyzer->getSource()->inferred_has_mutation = true;
            }

            $statements_analyzer->getSource()->inferred_impure = true;
        }

        if (!$config->remember_property_assignments_after_call
            && !$method_storage->mutation_free
            && !$method_pure_compatible
        ) {
            $context->removeAllObjectVars();
        } elseif ($method_storage->this_property_mutations) {
            foreach ($method_storage->this_property_mutations as $name => $_) {
                $mutation_var_id = $lhs_var_id . '->' . $name;

                $this_property_didnt_exist = $lhs_var_id === '$this'
                    && isset($context->vars_in_scope[$mutation_var_id])
                    && !isset($class_storage->declaring_property_ids[$name]);

                $context->remove($mutation_var_id);

                if ($this_property_didnt_exist) {
                    $context->vars_in_scope[$mutation_var_id] = Type::getMixed();
                }
            }
        }

        return $can_memoize;
    }
}
