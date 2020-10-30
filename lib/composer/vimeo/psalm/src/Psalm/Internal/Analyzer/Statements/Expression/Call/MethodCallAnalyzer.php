<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InvalidMethodCall;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\NullReference;
use Psalm\Issue\PossiblyFalseReference;
use Psalm\Issue\PossiblyInvalidMethodCall;
use Psalm\Issue\PossiblyNullReference;
use Psalm\Issue\PossiblyUndefinedMethod;
use Psalm\Issue\TooFewArguments;
use Psalm\Issue\TooManyArguments;
use Psalm\Issue\UndefinedInterfaceMethod;
use Psalm\Issue\UndefinedMagicMethod;
use Psalm\Issue\UndefinedMethod;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use function count;
use function is_string;
use function array_reduce;

/**
 * @internal
 */
class MethodCallAnalyzer extends \Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        Context $context,
        bool $real_method_call = true
    ) : bool {
        $was_inside_call = $context->inside_call;

        $context->inside_call = true;

        $was_inside_use = $context->inside_use;
        $context->inside_use = true;

        $existing_stmt_var_type = null;

        if (!$real_method_call) {
            $existing_stmt_var_type = $statements_analyzer->node_data->getType($stmt->var);
        }

        if ($existing_stmt_var_type) {
            $statements_analyzer->node_data->setType($stmt->var, $existing_stmt_var_type);
        } elseif (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->var, $context) === false) {
            return false;
        }

        $context->inside_call = $was_inside_call;

        if (!$stmt->name instanceof PhpParser\Node\Identifier) {
            $context->inside_call = true;

            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context) === false) {
                return false;
            }
        }

        $context->inside_call = $was_inside_call;
        $context->inside_use = $was_inside_use;

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
            if (is_string($stmt->var->name) && $stmt->var->name === 'this' && !$statements_analyzer->getFQCLN()) {
                if (IssueBuffer::accepts(
                    new InvalidScope(
                        'Use of $this in non-class context',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }
            }
        }

        $lhs_var_id = ExpressionIdentifier::getArrayVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $class_type = $lhs_var_id && $context->hasVariable($lhs_var_id)
            ? $context->vars_in_scope[$lhs_var_id]
            : null;

        if ($stmt_var_type = $statements_analyzer->node_data->getType($stmt->var)) {
            $class_type = $stmt_var_type;
        } elseif (!$class_type) {
            $statements_analyzer->node_data->setType($stmt, Type::getMixed());
        }

        if (!$context->check_classes) {
            if (ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->args,
                null,
                null,
                true,
                $context
            ) === false) {
                return false;
            }

            return true;
        }

        if ($class_type
            && $stmt->name instanceof PhpParser\Node\Identifier
            && ($class_type->isNull() || $class_type->isVoid())
        ) {
            if (IssueBuffer::accepts(
                new NullReference(
                    'Cannot call method ' . $stmt->name->name . ' on null value',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->name)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }

            return true;
        }

        if ($class_type
            && $stmt->name instanceof PhpParser\Node\Identifier
            && $class_type->isNullable()
            && !$class_type->ignore_nullable_issues
        ) {
            if (IssueBuffer::accepts(
                new PossiblyNullReference(
                    'Cannot call method ' . $stmt->name->name . ' on possibly null value',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->name)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if ($class_type
            && $stmt->name instanceof PhpParser\Node\Identifier
            && $class_type->isFalsable()
            && !$class_type->ignore_falsable_issues
        ) {
            if (IssueBuffer::accepts(
                new PossiblyFalseReference(
                    'Cannot call method ' . $stmt->name->name . ' on possibly false value',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->name)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        $codebase = $statements_analyzer->getCodebase();

        $source = $statements_analyzer->getSource();

        if (!$class_type) {
            $class_type = Type::getMixed();
        }

        $lhs_types = $class_type->getAtomicTypes();

        $result = new Method\AtomicMethodCallAnalysisResult();

        $possible_new_class_types = [];
        foreach ($lhs_types as $lhs_type_part) {
            Method\AtomicMethodCallAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $codebase,
                $context,
                $lhs_type_part,
                $lhs_type_part instanceof Type\Atomic\TNamedObject
                    || $lhs_type_part instanceof Type\Atomic\TTemplateParam
                    ? $lhs_type_part
                    : null,
                false,
                $lhs_var_id,
                $result
            );
            if (isset($context->vars_in_scope[$lhs_var_id])
                && ($possible_new_class_type = $context->vars_in_scope[$lhs_var_id]) instanceof Type\Union
                && !$possible_new_class_type->equals($class_type)) {
                $possible_new_class_types[] = $context->vars_in_scope[$lhs_var_id];
            }
        }

        if (count($possible_new_class_types) > 0) {
            $class_type = array_reduce(
                $possible_new_class_types,
                function (?Type\Union $type_1, Type\Union $type_2) use ($codebase): Type\Union {
                    if ($type_1 === null) {
                        return $type_2;
                    }
                    return Type::combineUnionTypes($type_1, $type_2, $codebase);
                }
            );
        }

        if ($result->invalid_method_call_types) {
            $invalid_class_type = $result->invalid_method_call_types[0];

            if ($result->has_valid_method_call_type || $result->has_mixed_method_call) {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidMethodCall(
                        'Cannot call method on possible ' . $invalid_class_type . ' variable ' . $lhs_var_id,
                        new CodeLocation($source, $stmt->name)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // keep going
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidMethodCall(
                        'Cannot call method on ' . $invalid_class_type . ' variable ' . $lhs_var_id,
                        new CodeLocation($source, $stmt->name)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // keep going
                }
            }
        }

        if ($result->non_existent_magic_method_ids) {
            if ($context->check_methods) {
                if (IssueBuffer::accepts(
                    new UndefinedMagicMethod(
                        'Magic method ' . $result->non_existent_magic_method_ids[0] . ' does not exist',
                        new CodeLocation($source, $stmt->name),
                        $result->non_existent_magic_method_ids[0]
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // keep going
                }
            }
        }

        if ($result->non_existent_class_method_ids) {
            if ($context->check_methods) {
                if ($result->existent_method_ids || $result->has_mixed_method_call) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedMethod(
                            'Method ' . $result->non_existent_class_method_ids[0] . ' does not exist',
                            new CodeLocation($source, $stmt->name),
                            $result->non_existent_class_method_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // keep going
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UndefinedMethod(
                            'Method ' . $result->non_existent_class_method_ids[0] . ' does not exist',
                            new CodeLocation($source, $stmt->name),
                            $result->non_existent_class_method_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // keep going
                    }
                }
            }

            return true;
        }

        if ($result->non_existent_interface_method_ids) {
            if ($context->check_methods) {
                if ($result->existent_method_ids || $result->has_mixed_method_call) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedMethod(
                            'Method ' . $result->non_existent_interface_method_ids[0] . ' does not exist',
                            new CodeLocation($source, $stmt->name),
                            $result->non_existent_interface_method_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // keep going
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UndefinedInterfaceMethod(
                            'Method ' . $result->non_existent_interface_method_ids[0] . ' does not exist',
                            new CodeLocation($source, $stmt->name),
                            $result->non_existent_interface_method_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // keep going
                    }
                }
            }

            return true;
        }

        if ($result->too_many_arguments && $result->too_many_arguments_method_ids) {
            $error_method_id = $result->too_many_arguments_method_ids[0];

            if (IssueBuffer::accepts(
                new TooManyArguments(
                    'Too many arguments for method ' . $error_method_id . ' - saw ' . count($stmt->args),
                    new CodeLocation($source, $stmt->name),
                    (string) $error_method_id
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if ($result->too_few_arguments && $result->too_few_arguments_method_ids) {
            $error_method_id = $result->too_few_arguments_method_ids[0];

            if (IssueBuffer::accepts(
                new TooFewArguments(
                    'Too few arguments for method ' . $error_method_id . ' saw ' . count($stmt->args),
                    new CodeLocation($source, $stmt->name),
                    (string) $error_method_id
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        $stmt_type = $result->return_type;

        if ($stmt_type) {
            $statements_analyzer->node_data->setType($stmt, $stmt_type);
        }

        if ($result->returns_by_ref) {
            if (!$stmt_type) {
                $stmt_type = Type::getMixed();
                $statements_analyzer->node_data->setType($stmt, $stmt_type);
            }

            $stmt_type->by_ref = $result->returns_by_ref;
        }

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
            && $stmt_type
        ) {
            $codebase->analyzer->addNodeType(
                $statements_analyzer->getFilePath(),
                $stmt->name,
                $stmt_type->getId(),
                $stmt
            );
        }

        if (!$result->existent_method_ids) {
            return self::checkMethodArgs(
                null,
                $stmt->args,
                null,
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer
            );
        }

        // if we called a method on this nullable variable, remove the nullable status here
        // because any further calls must have worked
        if ($lhs_var_id
            && !$class_type->isMixed()
            && $result->has_valid_method_call_type
            && !$result->has_mixed_method_call
            && !$result->invalid_method_call_types
            && ($class_type->from_docblock || $class_type->isNullable())
            && $real_method_call
        ) {
            $keys_to_remove = [];

            $class_type = clone $class_type;

            foreach ($class_type->getAtomicTypes() as $key => $type) {
                if (!$type instanceof TNamedObject) {
                    $keys_to_remove[] = $key;
                } else {
                    $type->from_docblock = false;
                }
            }

            foreach ($keys_to_remove as $key) {
                $class_type->removeType($key);
            }

            $class_type->from_docblock = false;

            $context->removeVarFromConflictingClauses($lhs_var_id, null, $statements_analyzer);

            $context->vars_in_scope[$lhs_var_id] = $class_type;
        }

        if ($lhs_var_id) {
            // TODO: Always defined? Always correct?
            $method_id = $result->existent_method_ids[0];
            if ($method_id instanceof MethodIdentifier) {
                // TODO: When should a method have a storage?
                if ($codebase->methods->hasStorage($method_id)) {
                    $storage = $codebase->methods->getStorage($method_id);
                    if ($storage->self_out_type) {
                        $self_out_type = $storage->self_out_type;
                        $context->vars_in_scope[$lhs_var_id] = $self_out_type;
                    }
                }
            } else {
                // TODO: When is method_id a string?
            }
        }

        return true;
    }
}
