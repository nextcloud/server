<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ArrayFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Stubs\Generator\StubsGenerator;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\UnionTemplateHandler;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InvalidPassByReference;
use Psalm\Issue\InvalidNamedArgument;
use Psalm\Issue\PossiblyUndefinedVariable;
use Psalm\Issue\TooFewArguments;
use Psalm\Issue\TooManyArguments;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TList;
use function strtolower;
use function strpos;
use function count;
use function in_array;
use function array_reverse;
use function is_string;

/**
 * @internal
 */
class ArgumentsAnalyzer
{
    /**
     * @param   array<int, PhpParser\Node\Arg>          $args
     * @param   array<int, FunctionLikeParameter>|null  $function_params
     * @param   array<string, array<string, array{Type\Union, 1?:int}>>|null   $generic_params
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        array $args,
        ?array $function_params,
        ?string $method_id,
        bool $allow_named_args,
        Context $context,
        ?TemplateResult $template_result = null
    ): ?bool {
        $last_param = $function_params
            ? $function_params[count($function_params) - 1]
            : null;

        // if this modifies the array type based on further args
        if ($method_id
            && in_array($method_id, ['array_push', 'array_unshift'], true)
            && $function_params
            && isset($args[0])
            && isset($args[1])
        ) {
            if (ArrayFunctionArgumentsAnalyzer::handleAddition(
                $statements_analyzer,
                $args,
                $context,
                $method_id === 'array_push'
            ) === false
            ) {
                return false;
            }

            return null;
        }

        if ($method_id && $method_id === 'array_splice' && $function_params && count($args) > 1) {
            if (ArrayFunctionArgumentsAnalyzer::handleSplice($statements_analyzer, $args, $context) === false) {
                return false;
            }

            return null;
        }

        if ($method_id === 'array_map') {
            $args = array_reverse($args, true);
        }

        foreach ($args as $argument_offset => $arg) {
            if ($function_params === null) {
                if (self::evaluateAribitraryParam(
                    $statements_analyzer,
                    $arg,
                    $context
                ) === false) {
                    return false;
                }

                continue;
            }

            $param = null;

            if ($arg->name && $allow_named_args) {
                foreach ($function_params as $candidate_param) {
                    if ($candidate_param->name === $arg->name->name) {
                        $param = $candidate_param;
                        break;
                    }
                }
            } elseif ($argument_offset < count($function_params)) {
                $param = $function_params[$argument_offset];
            } elseif ($last_param && $last_param->is_variadic) {
                $param = $last_param;
            }

            $by_ref = $param && $param->by_ref;

            $by_ref_type = null;

            if ($by_ref && $param) {
                $by_ref_type = $param->type ? clone $param->type : Type::getMixed();
            }

            if ($by_ref
                && $by_ref_type
                && !($arg->value instanceof PhpParser\Node\Expr\Closure
                    || $arg->value instanceof PhpParser\Node\Expr\ConstFetch
                    || $arg->value instanceof PhpParser\Node\Expr\ClassConstFetch
                    || $arg->value instanceof PhpParser\Node\Expr\FuncCall
                    || $arg->value instanceof PhpParser\Node\Expr\MethodCall
                    || $arg->value instanceof PhpParser\Node\Expr\StaticCall
                    || $arg->value instanceof PhpParser\Node\Expr\New_
                    || $arg->value instanceof PhpParser\Node\Expr\Assign
                    || $arg->value instanceof PhpParser\Node\Expr\Array_
                    || $arg->value instanceof PhpParser\Node\Expr\Ternary
                    || $arg->value instanceof PhpParser\Node\Expr\BinaryOp
                )
            ) {
                if (self::handleByRefFunctionArg(
                    $statements_analyzer,
                    $method_id,
                    $argument_offset,
                    $arg,
                    $context
                ) === false) {
                    return false;
                }

                continue;
            }

            $toggled_class_exists = false;

            if ($method_id === 'class_exists'
                && $argument_offset === 0
                && !$context->inside_class_exists
            ) {
                $context->inside_class_exists = true;
                $toggled_class_exists = true;
            }

            if (($arg->value instanceof PhpParser\Node\Expr\Closure
                    || $arg->value instanceof PhpParser\Node\Expr\ArrowFunction)
                && $template_result
                && $template_result->upper_bounds
                && $param
                && !$arg->value->getDocComment()
            ) {
                self::handleClosureArg(
                    $statements_analyzer,
                    $args,
                    $method_id,
                    $context,
                    $template_result,
                    $argument_offset,
                    $arg,
                    $param
                );
            }

            $was_inside_call = $context->inside_call;

            $context->inside_call = true;

            if (ExpressionAnalyzer::analyze($statements_analyzer, $arg->value, $context) === false) {
                return false;
            }

            if (!$was_inside_call) {
                $context->inside_call = false;
            }

            if (($argument_offset === 0 && $method_id === 'array_filter' && count($args) === 2)
                || ($argument_offset > 0 && $method_id === 'array_map' && count($args) >= 2)
            ) {
                self::handleArrayMapFilterArrayArg(
                    $statements_analyzer,
                    $method_id,
                    $argument_offset,
                    $arg,
                    $context,
                    $template_result
                );
            }

            if ($toggled_class_exists) {
                $context->inside_class_exists = false;
            }
        }

        return null;
    }

    private static function handleArrayMapFilterArrayArg(
        StatementsAnalyzer $statements_analyzer,
        string $method_id,
        int $argument_offset,
        PhpParser\Node\Arg $arg,
        Context $context,
        ?TemplateResult &$template_result
    ) : void {
        $codebase = $statements_analyzer->getCodebase();

        $generic_param_type = new Type\Union([
            new Type\Atomic\TArray([
                Type::getArrayKey(),
                new Type\Union([
                    new Type\Atomic\TTemplateParam(
                        'ArrayValue' . $argument_offset,
                        Type::getMixed(),
                        $method_id
                    )
                ])
            ])
        ]);

        $template_types = ['ArrayValue' . $argument_offset => [$method_id => [Type::getMixed()]]];

        $replace_template_result = new \Psalm\Internal\Type\TemplateResult(
            $template_types,
            []
        );

        $existing_type = $statements_analyzer->node_data->getType($arg->value);

        \Psalm\Internal\Type\UnionTemplateHandler::replaceTemplateTypesWithStandins(
            $generic_param_type,
            $replace_template_result,
            $codebase,
            $statements_analyzer,
            $existing_type,
            $argument_offset,
            'fn-' . ($context->calling_method_id ?: $context->calling_function_id)
        );

        if ($replace_template_result->upper_bounds) {
            if (!$template_result) {
                $template_result = new TemplateResult([], []);
            }

            $template_result->upper_bounds += $replace_template_result->upper_bounds;
        }
    }

    /**
     * @param   array<int, PhpParser\Node\Arg>  $args
     */
    private static function handleClosureArg(
        StatementsAnalyzer $statements_analyzer,
        array $args,
        ?string $method_id,
        Context $context,
        TemplateResult $template_result,
        int $argument_offset,
        PhpParser\Node\Arg $arg,
        FunctionLikeParameter $param
    ) : void {
        if (!$param->type) {
            return;
        }

        $codebase = $statements_analyzer->getCodebase();

        if (($argument_offset === 1 && $method_id === 'array_filter' && count($args) === 2)
            || ($argument_offset === 0 && $method_id === 'array_map' && count($args) >= 2)
        ) {
            $function_like_params = [];

            foreach ($template_result->upper_bounds as $template_name => $_) {
                $function_like_params[] = new \Psalm\Storage\FunctionLikeParameter(
                    'function',
                    false,
                    new Type\Union([
                        new Type\Atomic\TTemplateParam(
                            $template_name,
                            Type::getMixed(),
                            $method_id
                        )
                    ])
                );
            }

            $replaced_type = new Type\Union([
                new Type\Atomic\TCallable(
                    'callable',
                    array_reverse($function_like_params)
                )
            ]);
        } else {
            $replaced_type = clone $param->type;
        }

        $replace_template_result = new \Psalm\Internal\Type\TemplateResult(
            $template_result->upper_bounds,
            []
        );

        $replaced_type = \Psalm\Internal\Type\UnionTemplateHandler::replaceTemplateTypesWithStandins(
            $replaced_type,
            $replace_template_result,
            $codebase,
            $statements_analyzer,
            null,
            null,
            null,
            'fn-' . ($context->calling_method_id ?: $context->calling_function_id)
        );

        $replaced_type->replaceTemplateTypesWithArgTypes(
            $replace_template_result,
            $codebase
        );

        $closure_id = strtolower($statements_analyzer->getFilePath())
            . ':' . $arg->value->getLine()
            . ':' . (int)$arg->value->getAttribute('startFilePos')
            . ':-:closure';

        try {
            $closure_storage = $codebase->getClosureStorage(
                $statements_analyzer->getFilePath(),
                $closure_id
            );
        } catch (\UnexpectedValueException $e) {
            return;
        }

        foreach ($closure_storage->params as $closure_param_offset => $param_storage) {
            $param_type_inferred = $param_storage->type_inferred;

            $newly_inferred_type = null;
            $has_different_docblock_type = false;

            if ($param_storage->type && !$param_type_inferred) {
                if ($param_storage->type !== $param_storage->signature_type) {
                    $has_different_docblock_type = true;
                }
            }

            if (!$has_different_docblock_type) {
                foreach ($replaced_type->getAtomicTypes() as $replaced_type_part) {
                    if ($replaced_type_part instanceof Type\Atomic\TCallable
                        || $replaced_type_part instanceof Type\Atomic\TClosure
                    ) {
                        if (isset($replaced_type_part->params[$closure_param_offset]->type)
                            && !$replaced_type_part->params[$closure_param_offset]->type->hasTemplate()
                        ) {
                            if ($param_storage->type && !$param_type_inferred) {
                                $type_match_found = UnionTypeComparator::isContainedBy(
                                    $codebase,
                                    $replaced_type_part->params[$closure_param_offset]->type,
                                    $param_storage->type
                                );

                                if (!$type_match_found) {
                                    continue;
                                }
                            }

                            if (!$newly_inferred_type) {
                                $newly_inferred_type = $replaced_type_part->params[$closure_param_offset]->type;
                            } else {
                                $newly_inferred_type = Type::combineUnionTypes(
                                    $newly_inferred_type,
                                    $replaced_type_part->params[$closure_param_offset]->type,
                                    $codebase
                                );
                            }
                        }
                    }
                }
            }

            if ($newly_inferred_type) {
                $param_storage->type = $newly_inferred_type;
                $param_storage->type_inferred = true;
            }

            if ($param_storage->type && ($method_id === 'array_map' || $method_id === 'array_filter')) {
                ArrayFetchAnalyzer::taintArrayFetch(
                    $statements_analyzer,
                    $args[1 - $argument_offset]->value,
                    null,
                    $param_storage->type,
                    Type::getMixed()
                );
            }
        }
    }

    /**
     * @param   array<int, PhpParser\Node\Arg>          $args
     * @param   string|MethodIdentifier|null  $method_id
     * @param   array<int,FunctionLikeParameter>        $function_params
     *
     * @return  false|null
     */
    public static function checkArgumentsMatch(
        StatementsAnalyzer $statements_analyzer,
        array $args,
        $method_id,
        array $function_params,
        ?FunctionLikeStorage $function_storage,
        ?ClassLikeStorage $class_storage,
        ?TemplateResult $class_template_result,
        CodeLocation $code_location,
        Context $context
    ): ?bool {
        $in_call_map = $method_id ? InternalCallMapHandler::inCallMap((string) $method_id) : false;

        $cased_method_id = (string) $method_id;

        $is_variadic = false;

        $fq_class_name = null;

        $codebase = $statements_analyzer->getCodebase();

        if ($method_id) {
            if (!$in_call_map && $method_id instanceof \Psalm\Internal\MethodIdentifier) {
                $fq_class_name = $method_id->fq_class_name;
            }

            if ($function_storage) {
                $is_variadic = $function_storage->variadic;
            } elseif (is_string($method_id)) {
                $is_variadic = $codebase->functions->isVariadic(
                    $codebase,
                    strtolower($method_id),
                    $statements_analyzer->getRootFilePath()
                );
            } else {
                $is_variadic = $codebase->methods->isVariadic($method_id);
            }
        }

        if ($method_id instanceof \Psalm\Internal\MethodIdentifier) {
            $cased_method_id = $codebase->methods->getCasedMethodId($method_id);
        } elseif ($function_storage) {
            $cased_method_id = $function_storage->cased_name;
        }

        $calling_class_storage = $class_storage;

        $static_fq_class_name = $fq_class_name;
        $self_fq_class_name = $fq_class_name;

        if ($method_id instanceof \Psalm\Internal\MethodIdentifier) {
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

            if ($declaring_method_id && (string)$declaring_method_id !== (string)$method_id) {
                $self_fq_class_name = $declaring_method_id->fq_class_name;
                $class_storage = $codebase->classlike_storage_provider->get($self_fq_class_name);
            }

            $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);

            if ($appearing_method_id && $declaring_method_id !== $appearing_method_id) {
                $self_fq_class_name = $appearing_method_id->fq_class_name;
            }
        }

        if ($function_params) {
            foreach ($function_params as $function_param) {
                $is_variadic = $is_variadic || $function_param->is_variadic;
            }
        }

        $has_packed_var = false;

        $packed_var_definite_args = 0;

        foreach ($args as $arg) {
            if ($arg->unpack) {
                $arg_value_type = $statements_analyzer->node_data->getType($arg->value);

                if (!$arg_value_type
                    || !$arg_value_type->isSingle()
                    || !$arg_value_type->hasArray()
                ) {
                    $has_packed_var = true;
                    break;
                }

                foreach ($arg_value_type->getAtomicTypes() as $atomic_arg_type) {
                    if (!$atomic_arg_type instanceof TKeyedArray) {
                        $has_packed_var = true;
                        break 2;
                    }

                    $packed_var_definite_args = 0;

                    foreach ($atomic_arg_type->properties as $property_type) {
                        if ($property_type->possibly_undefined) {
                            $has_packed_var = true;
                        } else {
                            $packed_var_definite_args++;
                        }
                    }
                }
            }
        }

        if (!$has_packed_var) {
            $packed_var_definite_args = \max(0, $packed_var_definite_args - 1);
        }

        $last_param = $function_params
            ? $function_params[count($function_params) - 1]
            : null;

        $template_result = null;

        $class_generic_params = $class_template_result
            ? $class_template_result->upper_bounds
            : [];

        if ($function_storage) {
            $template_types = CallAnalyzer::getTemplateTypesForCall(
                $codebase,
                $class_storage,
                $self_fq_class_name,
                $calling_class_storage,
                $function_storage->template_types ?: []
            );

            if ($template_types) {
                $template_result = $class_template_result;

                if (!$template_result) {
                    $template_result = new TemplateResult($template_types, []);
                } elseif (!$template_result->template_types) {
                    $template_result->template_types = $template_types;
                }

                foreach ($args as $argument_offset => $arg) {
                    $function_param = null;

                    if ($arg->name && $function_storage->allow_named_arg_calls) {
                        foreach ($function_params as $candidate_param) {
                            if ($candidate_param->name === $arg->name->name) {
                                $function_param = $candidate_param;
                                break;
                            }
                        }
                    } elseif ($argument_offset < count($function_params)) {
                        $function_param = $function_params[$argument_offset];
                    } elseif ($last_param && $last_param->is_variadic) {
                        $function_param = $last_param;
                    }

                    if (!$function_param
                        || !$function_param->type
                    ) {
                        continue;
                    }

                    $arg_value_type = $statements_analyzer->node_data->getType($arg->value);

                    if (!$arg_value_type) {
                        continue;
                    }

                    UnionTemplateHandler::replaceTemplateTypesWithStandins(
                        $function_param->type,
                        $template_result,
                        $codebase,
                        $statements_analyzer,
                        $arg_value_type,
                        $argument_offset,
                        $context->self,
                        $context->calling_method_id ?: $context->calling_function_id,
                        false
                    );

                    if (!$class_template_result) {
                        $template_result->upper_bounds = [];
                    }
                }
            }
        }

        foreach ($class_generic_params as $template_name => $type_map) {
            foreach ($type_map as $class => $type) {
                $class_generic_params[$template_name][$class][0] = clone $type[0];
            }
        }

        $function_param_count = count($function_params);

        if (count($function_params) > count($args) && !$has_packed_var) {
            for ($i = count($args), $iMax = count($function_params); $i < $iMax; $i++) {
                if ($function_params[$i]->default_type
                    && $function_params[$i]->type
                    && $function_params[$i]->type->hasTemplate()
                    && $function_params[$i]->default_type->hasLiteralValue()
                ) {
                    ArgumentAnalyzer::checkArgumentMatches(
                        $statements_analyzer,
                        $cased_method_id,
                        $self_fq_class_name,
                        $static_fq_class_name,
                        $code_location,
                        $function_params[$i],
                        $i,
                        $i,
                        $function_storage ? $function_storage->allow_named_arg_calls : true,
                        new PhpParser\Node\Arg(
                            StubsGenerator::getExpressionFromType($function_params[$i]->default_type)
                        ),
                        $function_params[$i]->default_type,
                        $context,
                        $class_generic_params,
                        $template_result,
                        $function_storage ? $function_storage->specialize_call : true,
                        $in_call_map
                    );
                }
            }
        }

        if ($method_id === 'preg_match_all' && count($args) > 3) {
            $args = array_reverse($args, true);
        }

        foreach ($args as $argument_offset => $arg) {
            $arg_function_params = [];

            if ($arg->unpack && $function_param_count > $argument_offset) {
                for ($i = $argument_offset; $i < $function_param_count; $i++) {
                    $arg_function_params[] = $function_params[$i];
                }
            } elseif ($arg->name && $function_storage && $function_storage->allow_named_arg_calls) {
                foreach ($function_params as $candidate_param) {
                    if ($candidate_param->name === $arg->name->name) {
                        $arg_function_params = [$candidate_param];
                        break;
                    }
                }

                if (!$arg_function_params) {
                    if (IssueBuffer::accepts(
                        new InvalidNamedArgument(
                            'Parameter $' . $arg->name->name . ' does not exist on function '
                                . ($cased_method_id ?: $method_id),
                            new CodeLocation($statements_analyzer, $arg->name),
                            (string) $method_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            } elseif ($function_param_count > $argument_offset) {
                $arg_function_params = [$function_params[$argument_offset]];
            } elseif ($last_param && $last_param->is_variadic) {
                $arg_function_params = [$last_param];
            }

            if ($arg_function_params
                && $arg_function_params[0]->by_ref
                && $method_id !== 'extract'
            ) {
                if (self::handlePossiblyMatchingByRefParam(
                    $statements_analyzer,
                    $codebase,
                    (string) $method_id,
                    $cased_method_id,
                    $last_param,
                    $function_params,
                    $argument_offset,
                    $arg,
                    $context,
                    $template_result
                ) === false) {
                    return null;
                }
            }

            $arg_value_type = $statements_analyzer->node_data->getType($arg->value);

            foreach ($arg_function_params as $i => $function_param) {
                if (ArgumentAnalyzer::checkArgumentMatches(
                    $statements_analyzer,
                    $cased_method_id,
                    $self_fq_class_name,
                    $static_fq_class_name,
                    $code_location,
                    $function_param,
                    $argument_offset + $i,
                    $i,
                    $function_storage ? $function_storage->allow_named_arg_calls : true,
                    $arg,
                    $arg_value_type,
                    $context,
                    $class_generic_params,
                    $template_result,
                    $function_storage ? $function_storage->specialize_call : true,
                    $in_call_map
                ) === false) {
                    return false;
                }
            }
        }

        if ($method_id === 'array_map' || $method_id === 'array_filter') {
            if ($method_id === 'array_map' && count($args) < 2) {
                if (IssueBuffer::accepts(
                    new TooFewArguments(
                        'Too few arguments for ' . $method_id,
                        $code_location,
                        $method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } elseif ($method_id === 'array_filter' && count($args) < 1) {
                if (IssueBuffer::accepts(
                    new TooFewArguments(
                        'Too few arguments for ' . $method_id,
                        $code_location,
                        $method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            ArrayFunctionArgumentsAnalyzer::checkArgumentsMatch(
                $statements_analyzer,
                $context,
                $args,
                $method_id,
                $context->check_functions
            );

            return null;
        }

        if (!$is_variadic
            && count($args) > count($function_params)
            && (!count($function_params) || $function_params[count($function_params) - 1]->name !== '...=')
            && ($in_call_map
                || !$function_storage instanceof \Psalm\Storage\MethodStorage
                || $function_storage->is_static
                || ($method_id instanceof MethodIdentifier
                    && $method_id->method_name === '__construct'))
        ) {
            if (IssueBuffer::accepts(
                new TooManyArguments(
                    'Too many arguments for ' . ($cased_method_id ?: $method_id)
                        . ' - expecting ' . count($function_params) . ' but saw ' . count($args),
                    $code_location,
                    (string) $method_id
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            return null;
        }

        if (!$has_packed_var && count($args) < count($function_params)) {
            if ($function_storage) {
                $expected_param_count = $function_storage->required_param_count;
            } else {
                for ($i = 0, $j = count($function_params); $i < $j; ++$i) {
                    $param = $function_params[$i];

                    if ($param->is_optional || $param->is_variadic) {
                        break;
                    }
                }

                $expected_param_count = $i;
            }

            for ($i = count($args) + $packed_var_definite_args, $j = count($function_params); $i < $j; ++$i) {
                $param = $function_params[$i];

                if (!$param->is_optional
                    && !$param->is_variadic
                    && ($in_call_map
                        || !$function_storage instanceof \Psalm\Storage\MethodStorage
                        || $function_storage->is_static
                        || ($method_id instanceof MethodIdentifier
                            && $method_id->method_name === '__construct'))
                ) {
                    if (IssueBuffer::accepts(
                        new TooFewArguments(
                            'Too few arguments for ' . $cased_method_id
                                . ' - expecting ' . $expected_param_count
                                . ' but saw ' . (count($args) + $packed_var_definite_args),
                            $code_location,
                            (string) $method_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    break;
                }

                if ($param->is_optional
                    && $param->type
                    && $param->default_type
                    && !$param->is_variadic
                    && $template_result
                ) {
                    UnionTemplateHandler::replaceTemplateTypesWithStandins(
                        $param->type,
                        $template_result,
                        $codebase,
                        $statements_analyzer,
                        clone $param->default_type,
                        $i,
                        $context->self,
                        $context->calling_method_id ?: $context->calling_function_id,
                        true
                    );
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, FunctionLikeParameter> $function_params
     * @return false|null
     */
    private static function handlePossiblyMatchingByRefParam(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        ?string $method_id,
        ?string $cased_method_id,
        ?FunctionLikeParameter $last_param,
        array $function_params,
        int $argument_offset,
        PhpParser\Node\Arg $arg,
        Context $context,
        ?TemplateResult $template_result
    ): ?bool {
        if ($arg->value instanceof PhpParser\Node\Scalar
            || $arg->value instanceof PhpParser\Node\Expr\Cast
            || $arg->value instanceof PhpParser\Node\Expr\Array_
            || $arg->value instanceof PhpParser\Node\Expr\ClassConstFetch
            || $arg->value instanceof PhpParser\Node\Expr\BinaryOp
            || $arg->value instanceof PhpParser\Node\Expr\Ternary
            || (
                (
                $arg->value instanceof PhpParser\Node\Expr\ConstFetch
                    || $arg->value instanceof PhpParser\Node\Expr\FuncCall
                    || $arg->value instanceof PhpParser\Node\Expr\MethodCall
                    || $arg->value instanceof PhpParser\Node\Expr\StaticCall
                ) && (
                    !($arg_value_type = $statements_analyzer->node_data->getType($arg->value))
                    || !$arg_value_type->by_ref
                )
            )
        ) {
            if (IssueBuffer::accepts(
                new InvalidPassByReference(
                    'Parameter ' . ($argument_offset + 1) . ' of ' . $cased_method_id . ' expects a variable',
                    new CodeLocation($statements_analyzer->getSource(), $arg->value)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            return false;
        }

        if (!in_array(
            $method_id,
            [
                'ksort', 'asort', 'krsort', 'arsort', 'natcasesort', 'natsort',
                'reset', 'end', 'next', 'prev', 'array_pop', 'array_shift',
                'array_push', 'array_unshift', 'socket_select', 'array_splice',
            ],
            true
        )) {
            $by_ref_type = null;
            $by_ref_out_type = null;

            $check_null_ref = true;

            if ($last_param) {
                if ($argument_offset < count($function_params)) {
                    $function_param = $function_params[$argument_offset];
                } else {
                    $function_param = $last_param;
                }

                $by_ref_type = $function_param->type;
                $by_ref_out_type = $function_param->out_type;

                if ($by_ref_type && $by_ref_type->isNullable()) {
                    $check_null_ref = false;
                }

                if ($template_result && $by_ref_type) {
                    $original_by_ref_type = clone $by_ref_type;

                    $by_ref_type = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                        clone $by_ref_type,
                        $template_result,
                        $codebase,
                        $statements_analyzer,
                        $statements_analyzer->node_data->getType($arg->value),
                        $argument_offset,
                        'fn-' . ($context->calling_method_id ?: $context->calling_function_id)
                    );

                    if ($template_result->upper_bounds) {
                        $original_by_ref_type->replaceTemplateTypesWithArgTypes(
                            $template_result,
                            $codebase
                        );

                        $by_ref_type = $original_by_ref_type;
                    }
                }

                if ($template_result && $by_ref_out_type) {
                    $original_by_ref_out_type = clone $by_ref_out_type;

                    $by_ref_out_type = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                        clone $by_ref_out_type,
                        $template_result,
                        $codebase,
                        $statements_analyzer,
                        $statements_analyzer->node_data->getType($arg->value),
                        $argument_offset,
                        'fn-' . ($context->calling_method_id ?: $context->calling_function_id)
                    );

                    if ($template_result->upper_bounds) {
                        $original_by_ref_out_type->replaceTemplateTypesWithArgTypes(
                            $template_result,
                            $codebase
                        );

                        $by_ref_out_type = $original_by_ref_out_type;
                    }
                }

                if ($by_ref_type && $function_param->is_variadic && $arg->unpack) {
                    $by_ref_type = new Type\Union([
                        new Type\Atomic\TArray([
                            Type::getInt(),
                            $by_ref_type,
                        ]),
                    ]);
                }
            }

            $by_ref_type = $by_ref_type ?: Type::getMixed();

            AssignmentAnalyzer::assignByRefParam(
                $statements_analyzer,
                $arg->value,
                $by_ref_type,
                $by_ref_out_type ?: $by_ref_type,
                $context,
                $method_id && (strpos($method_id, '::') !== false || !InternalCallMapHandler::inCallMap($method_id)),
                $check_null_ref
            );
        }

        return null;
    }

    /**
     * @return false|null
     */
    private static function evaluateAribitraryParam(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Arg $arg,
        Context $context
    ): ?bool {
        // there are a bunch of things we want to evaluate even when we don't
        // know what function/method is being called
        if ($arg->value instanceof PhpParser\Node\Expr\Closure
            || $arg->value instanceof PhpParser\Node\Expr\ConstFetch
            || $arg->value instanceof PhpParser\Node\Expr\ClassConstFetch
            || $arg->value instanceof PhpParser\Node\Expr\FuncCall
            || $arg->value instanceof PhpParser\Node\Expr\MethodCall
            || $arg->value instanceof PhpParser\Node\Expr\StaticCall
            || $arg->value instanceof PhpParser\Node\Expr\New_
            || $arg->value instanceof PhpParser\Node\Expr\Cast
            || $arg->value instanceof PhpParser\Node\Expr\Assign
            || $arg->value instanceof PhpParser\Node\Expr\ArrayDimFetch
            || $arg->value instanceof PhpParser\Node\Expr\PropertyFetch
            || $arg->value instanceof PhpParser\Node\Expr\Array_
            || $arg->value instanceof PhpParser\Node\Expr\BinaryOp
            || $arg->value instanceof PhpParser\Node\Expr\Ternary
            || $arg->value instanceof PhpParser\Node\Scalar\Encapsed
            || $arg->value instanceof PhpParser\Node\Expr\PostInc
            || $arg->value instanceof PhpParser\Node\Expr\PostDec
            || $arg->value instanceof PhpParser\Node\Expr\PreInc
            || $arg->value instanceof PhpParser\Node\Expr\PreDec
        ) {
            $was_inside_call = $context->inside_call;
            $context->inside_call = true;

            if (ExpressionAnalyzer::analyze($statements_analyzer, $arg->value, $context) === false) {
                return false;
            }

            if (!$was_inside_call) {
                $context->inside_call = false;
            }
        }

        if ($arg->value instanceof PhpParser\Node\Expr\PropertyFetch
            && $arg->value->name instanceof PhpParser\Node\Identifier
        ) {
            $var_id = '$' . $arg->value->name->name;
        } else {
            $var_id = ExpressionIdentifier::getVarId(
                $arg->value,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
            );
        }

        if ($var_id) {
            if ($arg->value instanceof PhpParser\Node\Expr\Variable) {
                $statements_analyzer->registerPossiblyUndefinedVariable($var_id, $arg->value);
            }

            if (!$context->hasVariable($var_id)
                || $context->vars_in_scope[$var_id]->isNull()
            ) {
                if (!isset($context->vars_in_scope[$var_id])
                    && $arg->value instanceof PhpParser\Node\Expr\Variable
                ) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedVariable(
                            'Variable ' . $var_id
                                . ' must be defined prior to use within an unknown function or method',
                            new CodeLocation($statements_analyzer->getSource(), $arg->value)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                // we don't know if it exists, assume it's passed by reference
                $context->vars_in_scope[$var_id] = Type::getMixed();
                $context->vars_possibly_in_scope[$var_id] = true;
            } else {
                $was_inside_call = $context->inside_call;
                $context->inside_call = true;
                ExpressionAnalyzer::analyze($statements_analyzer, $arg->value, $context);
                $context->inside_call = $was_inside_call;

                $context->removeVarFromConflictingClauses(
                    $var_id,
                    $context->vars_in_scope[$var_id],
                    $statements_analyzer
                );

                foreach ($context->vars_in_scope[$var_id]->getAtomicTypes() as $type) {
                    if ($type instanceof TArray && $type->type_params[1]->isEmpty()) {
                        $context->vars_in_scope[$var_id]->removeType('array');
                        $context->vars_in_scope[$var_id]->addType(
                            new TArray(
                                [Type::getArrayKey(), Type::getMixed()]
                            )
                        );
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return false|null
     */
    private static function handleByRefFunctionArg(
        StatementsAnalyzer $statements_analyzer,
        ?string $method_id,
        int $argument_offset,
        PhpParser\Node\Arg $arg,
        Context $context
    ): ?bool {
        $var_id = ExpressionIdentifier::getVarId(
            $arg->value,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $builtin_array_functions = [
            'ksort', 'asort', 'krsort', 'arsort', 'natcasesort', 'natsort',
            'reset', 'end', 'next', 'prev', 'array_pop', 'array_shift',
        ];

        if (($var_id && isset($context->vars_in_scope[$var_id]))
            || ($method_id
                && in_array(
                    $method_id,
                    $builtin_array_functions,
                    true
                ))
        ) {
            $was_inside_assignment = $context->inside_assignment;
            $context->inside_assignment = true;

            // if the variable is in scope, get or we're in a special array function,
            // figure out its type before proceeding
            if (ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $arg->value,
                $context
            ) === false) {
                return false;
            }

            $context->inside_assignment = $was_inside_assignment;
        }

        // special handling for array sort
        if ($argument_offset === 0
            && $method_id
            && in_array(
                $method_id,
                $builtin_array_functions,
                true
            )
        ) {
            if (in_array($method_id, ['array_pop', 'array_shift'], true)) {
                ArrayFunctionArgumentsAnalyzer::handleByRefArrayAdjustment(
                    $statements_analyzer,
                    $arg,
                    $context,
                    $method_id === 'array_shift'
                );

                return null;
            }

            // noops
            if (in_array($method_id, ['reset', 'end', 'next', 'prev', 'ksort'], true)) {
                return null;
            }

            if (($arg_value_type = $statements_analyzer->node_data->getType($arg->value))
                && $arg_value_type->hasArray()
            ) {
                /**
                 * @psalm-suppress PossiblyUndefinedStringArrayOffset
                 * @var TArray|TList|TKeyedArray
                 */
                $array_type = $arg_value_type->getAtomicTypes()['array'];

                if ($array_type instanceof TKeyedArray) {
                    $array_type = $array_type->getGenericArrayType();
                }

                if ($array_type instanceof TList) {
                    $array_type = new TArray([Type::getInt(), $array_type->type_param]);
                }

                $by_ref_type = new Type\Union([clone $array_type]);

                AssignmentAnalyzer::assignByRefParam(
                    $statements_analyzer,
                    $arg->value,
                    $by_ref_type,
                    $by_ref_type,
                    $context,
                    false
                );

                return null;
            }
        }

        if ($method_id === 'socket_select') {
            if (ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $arg->value,
                $context
            ) === false) {
                return false;
            }
        }

        if (!$arg->value instanceof PhpParser\Node\Expr\Variable) {
            $suppressed_issues = $statements_analyzer->getSuppressedIssues();

            if (!in_array('EmptyArrayAccess', $suppressed_issues, true)) {
                $statements_analyzer->addSuppressedIssues(['EmptyArrayAccess']);
            }

            if (ExpressionAnalyzer::analyze($statements_analyzer, $arg->value, $context) === false) {
                return false;
            }

            if (!in_array('EmptyArrayAccess', $suppressed_issues, true)) {
                $statements_analyzer->removeSuppressedIssues(['EmptyArrayAccess']);
            }
        }

        return null;
    }
}
