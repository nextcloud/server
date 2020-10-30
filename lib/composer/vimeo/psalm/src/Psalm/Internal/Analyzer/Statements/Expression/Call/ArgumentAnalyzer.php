<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\CastAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\DataFlow\TaintSink;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\UnionTemplateHandler;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\InvalidScalarArgument;
use Psalm\Issue\InvalidLiteralArgument;
use Psalm\Issue\MixedArgument;
use Psalm\Issue\MixedArgumentTypeCoercion;
use Psalm\Issue\NoValue;
use Psalm\Issue\NullArgument;
use Psalm\Issue\PossiblyFalseArgument;
use Psalm\Issue\PossiblyInvalidArgument;
use Psalm\Issue\PossiblyNullArgument;
use Psalm\Issue\ArgumentTypeCoercion;
use Psalm\IssueBuffer;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TList;
use function strtolower;
use function strpos;
use function explode;
use function count;

/**
 * @internal
 */
class ArgumentAnalyzer
{
    /**
     * @param  array<string, array<string, array{Type\Union, 1?:int}>> $class_generic_params
     * @return false|null
     */
    public static function checkArgumentMatches(
        StatementsAnalyzer $statements_analyzer,
        ?string $cased_method_id,
        ?string $self_fq_class_name,
        ?string $static_fq_class_name,
        CodeLocation $function_call_location,
        ?FunctionLikeParameter $function_param,
        int $argument_offset,
        int $unpacked_argument_offset,
        bool $allow_named_args,
        PhpParser\Node\Arg $arg,
        ?Type\Union $arg_value_type,
        Context $context,
        array $class_generic_params,
        ?TemplateResult $template_result,
        bool $specialize_taint,
        bool $in_call_map
    ): ?bool {
        $codebase = $statements_analyzer->getCodebase();

        if (!$arg_value_type) {
            if ($function_param && !$function_param->by_ref) {
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                }

                $param_type = $function_param->type;

                if ($function_param->is_variadic
                    && $param_type
                    && $param_type->hasArray()
                ) {
                    /**
                     * @psalm-suppress PossiblyUndefinedStringArrayOffset
                     * @var TList|TArray
                     */
                    $array_type = $param_type->getAtomicTypes()['array'];

                    if ($array_type instanceof TList) {
                        $param_type = $array_type->type_param;
                    } else {
                        $param_type = $array_type->type_params[1];
                    }
                }

                if ($param_type && !$param_type->hasMixed()) {
                    if (IssueBuffer::accepts(
                        new MixedArgument(
                            'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id
                                . ' cannot be mixed, expecting ' . $param_type,
                            new CodeLocation($statements_analyzer->getSource(), $arg->value),
                            $cased_method_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            return null;
        }

        if (!$function_param) {
            return null;
        }

        if ($function_param->expect_variable
            && $arg_value_type->isSingleStringLiteral()
            && !$arg->value instanceof PhpParser\Node\Scalar\MagicConst
            && !$arg->value instanceof PhpParser\Node\Expr\ConstFetch
        ) {
            $values = \preg_split('//u', $arg_value_type->getSingleStringLiteral()->value, -1, \PREG_SPLIT_NO_EMPTY);

            $prev_ord = 0;

            $gt_count = 0;

            foreach ($values as $value) {
                /**
                 * @var int
                 * @psalm-suppress UnnecessaryVarAnnotation
                 */
                $ord = \mb_ord($value);

                if ($ord > $prev_ord) {
                    $gt_count++;
                }

                $prev_ord = $ord;
            }

            if (count($values) < 12 || ($gt_count / count($values)) < 0.8) {
                if (IssueBuffer::accepts(
                    new InvalidLiteralArgument(
                        'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id
                            . ' expects a non-literal value, ' . $arg_value_type->getId() . ' provided',
                        new CodeLocation($statements_analyzer->getSource(), $arg->value),
                        $cased_method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }

        if (self::checkFunctionLikeTypeMatches(
            $statements_analyzer,
            $codebase,
            $cased_method_id,
            $self_fq_class_name,
            $static_fq_class_name,
            $function_call_location,
            $function_param,
            $allow_named_args,
            $arg_value_type,
            $argument_offset,
            $unpacked_argument_offset,
            $arg,
            $context,
            $class_generic_params,
            $template_result,
            $specialize_taint,
            $in_call_map
        ) === false) {
            return false;
        }

        return null;
    }

    /**
     * @param  array<string, array<string, array{Type\Union, 1?:int}>> $class_generic_params
     * @param  array<string, array<string, array{Type\Union, 1?:int}>> $generic_params
     * @param  array<string, array<string, array{Type\Union}>> $template_types
     * @return false|null
     */
    private static function checkFunctionLikeTypeMatches(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        ?string $cased_method_id,
        ?string $self_fq_class_name,
        ?string $static_fq_class_name,
        CodeLocation $function_call_location,
        FunctionLikeParameter $function_param,
        bool $allow_named_args,
        Type\Union $arg_type,
        int $argument_offset,
        int $unpacked_argument_offset,
        PhpParser\Node\Arg $arg,
        Context $context,
        ?array $class_generic_params,
        ?TemplateResult $template_result,
        bool $specialize_taint,
        bool $in_call_map
    ): ?bool {
        if (!$function_param->type) {
            if (!$codebase->infer_types_from_usage && !$statements_analyzer->data_flow_graph) {
                return null;
            }

            $param_type = Type::getMixed();
        } else {
            $param_type = clone $function_param->type;
        }

        $bindable_template_params = [];

        if ($template_result) {
            $bindable_template_params = $param_type->getTemplateTypes();
        }

        if ($class_generic_params) {
            $empty_generic_params = [];

            $empty_template_result = new TemplateResult($class_generic_params, $empty_generic_params);

            $arg_value_type = $statements_analyzer->node_data->getType($arg->value);

            $param_type = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                $param_type,
                $empty_template_result,
                $codebase,
                $statements_analyzer,
                $arg_value_type,
                $argument_offset,
                $context->self ?: 'fn-' . $context->calling_function_id
            );

            $arg_type = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                $arg_type,
                $empty_template_result,
                $codebase,
                $statements_analyzer,
                $arg_value_type,
                $argument_offset,
                $context->self ?: 'fn-' . $context->calling_function_id
            );
        }

        if ($template_result && $template_result->template_types) {
            $arg_type_param = $arg_type;

            if ($arg->unpack) {
                $arg_type_param = null;

                foreach ($arg_type->getAtomicTypes() as $arg_atomic_type) {
                    if ($arg_atomic_type instanceof Type\Atomic\TArray
                        || $arg_atomic_type instanceof Type\Atomic\TList
                        || $arg_atomic_type instanceof Type\Atomic\TKeyedArray
                    ) {
                        if ($arg_atomic_type instanceof Type\Atomic\TKeyedArray) {
                            $arg_type_param = $arg_atomic_type->getGenericValueType();
                        } elseif ($arg_atomic_type instanceof Type\Atomic\TList) {
                            $arg_type_param = $arg_atomic_type->type_param;
                        } else {
                            $arg_type_param = $arg_atomic_type->type_params[1];
                        }
                    } elseif ($arg_atomic_type instanceof Type\Atomic\TIterable) {
                        $arg_type_param = $arg_atomic_type->type_params[1];
                    } elseif ($arg_atomic_type instanceof Type\Atomic\TNamedObject) {
                        ForeachAnalyzer::getKeyValueParamsForTraversableObject(
                            $arg_atomic_type,
                            $codebase,
                            $key_type,
                            $arg_type_param
                        );
                    }
                }

                if (!$arg_type_param) {
                    $arg_type_param = Type::getMixed();
                    $arg_type_param->parent_nodes = $arg_type->parent_nodes;
                }
            }

            $param_type = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                $param_type,
                $template_result,
                $codebase,
                $statements_analyzer,
                $arg_type_param,
                $argument_offset,
                $context->self,
                $context->calling_method_id ?: $context->calling_function_id
            );

            foreach ($bindable_template_params as $template_type) {
                if (!isset(
                    $template_result->upper_bounds
                        [$template_type->param_name]
                        [$template_type->defining_class]
                )
                    && !isset(
                        $template_result->lower_bounds
                        [$template_type->param_name]
                        [$template_type->defining_class]
                    )
                ) {
                    $template_result->upper_bounds[$template_type->param_name][$template_type->defining_class] = [
                        clone $template_type->as,
                        0
                    ];
                }
            }
        }

        $parent_class = null;

        $classlike_storage = null;
        $static_classlike_storage = null;

        if ($self_fq_class_name) {
            $classlike_storage = $codebase->classlike_storage_provider->get($self_fq_class_name);
            $parent_class = $classlike_storage->parent_class;
            $static_classlike_storage = $classlike_storage;

            if ($static_fq_class_name && $static_fq_class_name !== $self_fq_class_name) {
                $static_classlike_storage = $codebase->classlike_storage_provider->get($static_fq_class_name);
            }
        }

        $fleshed_out_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
            $codebase,
            $param_type,
            $classlike_storage ? $classlike_storage->name : null,
            $static_classlike_storage ? $static_classlike_storage->name : null,
            $parent_class,
            true,
            false,
            $static_classlike_storage ? $static_classlike_storage->final : false
        );

        $fleshed_out_signature_type = $function_param->signature_type
            ? \Psalm\Internal\Type\TypeExpander::expandUnion(
                $codebase,
                $function_param->signature_type,
                $classlike_storage ? $classlike_storage->name : null,
                $static_classlike_storage ? $static_classlike_storage->name : null,
                $parent_class
            )
            : null;

        $unpacked_atomic_array = null;

        if ($arg->unpack) {
            if ($arg_type->hasMixed()) {
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                }

                if (IssueBuffer::accepts(
                    new MixedArgument(
                        'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id
                            . ' cannot be ' . $arg_type->getId() . ', expecting array',
                        new CodeLocation($statements_analyzer->getSource(), $arg->value),
                        $cased_method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                if ($cased_method_id) {
                    $arg_location = new CodeLocation($statements_analyzer->getSource(), $arg->value);

                    self::processTaintedness(
                        $statements_analyzer,
                        $cased_method_id,
                        $argument_offset,
                        $arg_location,
                        $function_call_location,
                        $function_param,
                        $arg_type,
                        $arg->value,
                        $context,
                        $specialize_taint
                    );
                }

                return null;
            }

            if ($arg_type->hasArray()) {
                /**
                 * @psalm-suppress PossiblyUndefinedStringArrayOffset
                 * @var Type\Atomic\TArray|Type\Atomic\TList|Type\Atomic\TKeyedArray
                 */
                $unpacked_atomic_array = $arg_type->getAtomicTypes()['array'];

                if ($unpacked_atomic_array instanceof Type\Atomic\TKeyedArray) {
                    if ($function_param->is_variadic) {
                        $arg_type = $unpacked_atomic_array->getGenericValueType();
                    } elseif ($codebase->php_major_version >= 8
                        && $allow_named_args
                        && isset($unpacked_atomic_array->properties[$function_param->name])
                    ) {
                        $arg_type = clone $unpacked_atomic_array->properties[$function_param->name];
                    } elseif ($unpacked_atomic_array->is_list
                        && isset($unpacked_atomic_array->properties[$unpacked_argument_offset])
                    ) {
                        $arg_type = clone $unpacked_atomic_array->properties[$unpacked_argument_offset];
                    } else {
                        $arg_type = Type::getMixed();
                    }
                } elseif ($unpacked_atomic_array instanceof Type\Atomic\TList) {
                    $arg_type = $unpacked_atomic_array->type_param;
                } else {
                    $arg_type = $unpacked_atomic_array->type_params[1];
                }
            } else {
                foreach ($arg_type->getAtomicTypes() as $atomic_type) {
                    if (!$atomic_type->isIterable($codebase)) {
                        if (IssueBuffer::accepts(
                            new InvalidArgument(
                                'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id
                                    . ' expects array, ' . $atomic_type->getId() . ' provided',
                                new CodeLocation($statements_analyzer->getSource(), $arg->value),
                                $cased_method_id
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }

                        continue;
                    }
                }

                return null;
            }
        }

        if (self::verifyType(
            $statements_analyzer,
            $arg_type,
            $fleshed_out_type,
            $fleshed_out_signature_type,
            $cased_method_id,
            $argument_offset,
            new CodeLocation($statements_analyzer->getSource(), $arg->value),
            $arg->value,
            $context,
            $function_param,
            $arg->unpack,
            $unpacked_atomic_array,
            $specialize_taint,
            $in_call_map,
            $function_call_location
        ) === false) {
            return false;
        }

        return null;
    }

    /**
     * @param Type\Atomic\TKeyedArray|Type\Atomic\TArray|Type\Atomic\TList $unpacked_atomic_array
     * @return  null|false
     */
    public static function verifyType(
        StatementsAnalyzer $statements_analyzer,
        Type\Union $input_type,
        Type\Union $param_type,
        ?Type\Union $signature_param_type,
        ?string $cased_method_id,
        int $argument_offset,
        CodeLocation $arg_location,
        PhpParser\Node\Expr $input_expr,
        Context $context,
        FunctionLikeParameter $function_param,
        bool $unpack,
        ?Type\Atomic $unpacked_atomic_array,
        bool $specialize_taint,
        bool $in_call_map,
        CodeLocation $function_call_location
    ): ?bool {
        $codebase = $statements_analyzer->getCodebase();

        if ($param_type->hasMixed()) {
            if ($codebase->infer_types_from_usage
                && !$input_type->hasMixed()
                && !$param_type->from_docblock
                && !$param_type->had_template
                && $cased_method_id
                && strpos($cased_method_id, '::')
                && !strpos($cased_method_id, '__')
            ) {
                $method_parts = explode('::', $cased_method_id);

                $method_id = new \Psalm\Internal\MethodIdentifier($method_parts[0], strtolower($method_parts[1]));
                $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                if ($declaring_method_id) {
                    $id_lc = strtolower((string) $declaring_method_id);
                    if (!isset($codebase->analyzer->possible_method_param_types[$id_lc][$argument_offset])) {
                        $codebase->analyzer->possible_method_param_types[$id_lc][$argument_offset]
                            = clone $input_type;
                    } else {
                        $codebase->analyzer->possible_method_param_types[$id_lc][$argument_offset]
                            = Type::combineUnionTypes(
                                $codebase->analyzer->possible_method_param_types[$id_lc][$argument_offset],
                                clone $input_type,
                                $codebase
                            );
                    }
                }
            }

            if ($cased_method_id) {
                self::processTaintedness(
                    $statements_analyzer,
                    $cased_method_id,
                    $argument_offset,
                    $arg_location,
                    $function_call_location,
                    $function_param,
                    $input_type,
                    $input_expr,
                    $context,
                    $specialize_taint
                );
            }

            return null;
        }

        $method_identifier = $cased_method_id ? ' of ' . $cased_method_id : '';

        if ($input_type->hasMixed()) {
            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                        instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
            }

            if (IssueBuffer::accepts(
                new MixedArgument(
                    'Argument ' . ($argument_offset + 1) . $method_identifier
                        . ' cannot be ' . $input_type->getId() . ', expecting ' .
                        $param_type,
                    $arg_location,
                    $cased_method_id
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            if ($input_type->isMixed()) {
                if (!$function_param->by_ref
                    && !($function_param->is_variadic xor $unpack)
                    && $cased_method_id !== 'echo'
                    && $cased_method_id !== 'print'
                    && (!$in_call_map || $context->strict_types)
                ) {
                    self::coerceValueAfterGatekeeperArgument(
                        $statements_analyzer,
                        $input_type,
                        false,
                        $input_expr,
                        $param_type,
                        $signature_param_type,
                        $context,
                        $unpack,
                        $unpacked_atomic_array
                    );
                }
            }

            if ($cased_method_id) {
                $input_type = self::processTaintedness(
                    $statements_analyzer,
                    $cased_method_id,
                    $argument_offset,
                    $arg_location,
                    $function_call_location,
                    $function_param,
                    $input_type,
                    $input_expr,
                    $context,
                    $specialize_taint
                );
            }

            if ($input_type->isMixed()) {
                return null;
            }
        }

        if ($input_type->isNever()) {
            if (IssueBuffer::accepts(
                new NoValue(
                    'This function or method call never returns output',
                    $arg_location
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            return null;
        }

        if (!$context->collect_initializations
            && !$context->collect_mutations
            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
            && (!(($parent_source = $statements_analyzer->getSource())
                    instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
        ) {
            $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
        }

        if ($function_param->by_ref) {
            $param_type->possibly_undefined = true;
        }

        if ($param_type->hasCallableType()
            && $param_type->isSingle()
            && $input_type->isSingleStringLiteral()
            && !\Psalm\Internal\Codebase\InternalCallMapHandler::inCallMap($input_type->getSingleStringLiteral()->value)
        ) {
            foreach ($input_type->getAtomicTypes() as $key => $atomic_type) {
                $candidate_callable = CallableTypeComparator::getCallableFromAtomic(
                    $codebase,
                    $atomic_type,
                    null,
                    $statements_analyzer
                );

                if ($candidate_callable) {
                    $input_type->removeType($key);
                    $input_type->addType($candidate_callable);
                }
            }
        }

        $union_comparison_results = new \Psalm\Internal\Type\Comparator\TypeComparisonResult();

        $type_match_found = UnionTypeComparator::isContainedBy(
            $codebase,
            $input_type,
            $param_type,
            true,
            true,
            $union_comparison_results
        );

        $replace_input_type = false;

        if ($union_comparison_results->replacement_union_type) {
            $replace_input_type = true;
            $input_type = $union_comparison_results->replacement_union_type;
        }

        if ($cased_method_id) {
            $old_input_type = $input_type;

            $input_type = self::processTaintedness(
                $statements_analyzer,
                $cased_method_id,
                $argument_offset,
                $arg_location,
                $function_call_location,
                $function_param,
                $input_type,
                $input_expr,
                $context,
                $specialize_taint
            );

            if ($old_input_type !== $input_type) {
                $replace_input_type = true;
            }
        }

        if ($type_match_found
            && $param_type->hasCallableType()
        ) {
            $potential_method_ids = [];

            foreach ($input_type->getAtomicTypes() as $input_type_part) {
                if ($input_type_part instanceof Type\Atomic\TKeyedArray) {
                    $potential_method_id = CallableTypeComparator::getCallableMethodIdFromTKeyedArray(
                        $input_type_part,
                        $codebase,
                        $context->calling_method_id,
                        $statements_analyzer->getFilePath()
                    );

                    if ($potential_method_id && $potential_method_id !== 'not-callable') {
                        $potential_method_ids[] = $potential_method_id;
                    }
                } elseif ($input_type_part instanceof Type\Atomic\TLiteralString
                    && strpos($input_type_part->value, '::')
                ) {
                    $parts = explode('::', $input_type_part->value);
                    $potential_method_ids[] = new \Psalm\Internal\MethodIdentifier(
                        $parts[0],
                        strtolower($parts[1])
                    );
                }
            }

            foreach ($potential_method_ids as $potential_method_id) {
                $codebase->methods->methodExists(
                    $potential_method_id,
                    $context->calling_method_id,
                    null,
                    $statements_analyzer,
                    $statements_analyzer->getFilePath()
                );
            }
        }

        if ($context->strict_types
            && !$input_type->hasArray()
            && !$param_type->from_docblock
            && $cased_method_id !== 'echo'
            && $cased_method_id !== 'print'
            && $cased_method_id !== 'sprintf'
        ) {
            $union_comparison_results->scalar_type_match_found = false;

            if ($union_comparison_results->to_string_cast) {
                $union_comparison_results->to_string_cast = false;
                $type_match_found = false;
            }
        }

        if ($union_comparison_results->type_coerced && !$input_type->hasMixed()) {
            if ($union_comparison_results->type_coerced_from_mixed) {
                if (IssueBuffer::accepts(
                    new MixedArgumentTypeCoercion(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type->getId() .
                            ', parent type ' . $input_type->getId() . ' provided',
                        $arg_location,
                        $cased_method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // keep soldiering on
                }
            } else {
                if (IssueBuffer::accepts(
                    new ArgumentTypeCoercion(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type->getId() .
                            ', parent type ' . $input_type->getId() . ' provided',
                        $arg_location,
                        $cased_method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // keep soldiering on
                }
            }
        }

        if ($union_comparison_results->to_string_cast && $cased_method_id !== 'echo' && $cased_method_id !== 'print') {
            if (IssueBuffer::accepts(
                new ImplicitToStringCast(
                    'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' .
                        $param_type->getId() . ', ' . $input_type->getId() . ' provided with a __toString method',
                    $arg_location
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if (!$type_match_found && !$union_comparison_results->type_coerced) {
            $types_can_be_identical = UnionTypeComparator::canBeContainedBy(
                $codebase,
                $input_type,
                $param_type,
                true,
                true
            );

            if ($union_comparison_results->scalar_type_match_found) {
                if ($cased_method_id !== 'echo' && $cased_method_id !== 'print') {
                    if (IssueBuffer::accepts(
                        new InvalidScalarArgument(
                            'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' .
                                $param_type->getId() . ', ' . $input_type->getId() . ' provided',
                            $arg_location,
                            $cased_method_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            } elseif ($types_can_be_identical) {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type->getId() .
                            ', possibly different type ' . $input_type->getId() . ' provided',
                        $arg_location,
                        $cased_method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type->getId() .
                            ', ' . $input_type->getId() . ' provided',
                        $arg_location,
                        $cased_method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            return null;
        }

        if ($input_expr instanceof PhpParser\Node\Scalar\String_
            || $input_expr instanceof PhpParser\Node\Expr\Array_
            || $input_expr instanceof PhpParser\Node\Expr\BinaryOp\Concat
        ) {
            foreach ($param_type->getAtomicTypes() as $param_type_part) {
                if ($param_type_part instanceof TClassString
                    && $input_expr instanceof PhpParser\Node\Scalar\String_
                    && $param_type->isSingle()
                ) {
                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $statements_analyzer,
                        $input_expr->value,
                        $arg_location,
                        $context->self,
                        $context->calling_method_id,
                        $statements_analyzer->getSuppressedIssues()
                    ) === false
                    ) {
                        return null;
                    }
                } elseif ($param_type_part instanceof TArray
                    && $input_expr instanceof PhpParser\Node\Expr\Array_
                ) {
                    foreach ($param_type_part->type_params[1]->getAtomicTypes() as $param_array_type_part) {
                        if ($param_array_type_part instanceof TClassString) {
                            foreach ($input_expr->items as $item) {
                                if ($item && $item->value instanceof PhpParser\Node\Scalar\String_) {
                                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                                        $statements_analyzer,
                                        $item->value->value,
                                        $arg_location,
                                        $context->self,
                                        $context->calling_method_id,
                                        $statements_analyzer->getSuppressedIssues()
                                    ) === false
                                    ) {
                                        return null;
                                    }
                                }
                            }
                        }
                    }
                } elseif ($param_type_part instanceof TCallable) {
                    $can_be_callable_like_array = false;
                    if ($param_type->hasArray()) {
                        /**
                         * @psalm-suppress PossiblyUndefinedStringArrayOffset
                         */
                        $param_array_type = $param_type->getAtomicTypes()['array'];

                        $row_type = null;
                        if ($param_array_type instanceof TList) {
                            $row_type = $param_array_type->type_param;
                        } elseif ($param_array_type instanceof TArray) {
                            $row_type = $param_array_type->type_params[1];
                        } elseif ($param_array_type instanceof Type\Atomic\TKeyedArray) {
                            $row_type = $param_array_type->getGenericArrayType()->type_params[1];
                        }

                        if ($row_type &&
                            ($row_type->hasMixed() || $row_type->hasString())
                        ) {
                            $can_be_callable_like_array = true;
                        }
                    }

                    if (!$can_be_callable_like_array) {
                        $function_ids = CallAnalyzer::getFunctionIdsFromCallableArg(
                            $statements_analyzer,
                            $input_expr
                        );

                        foreach ($function_ids as $function_id) {
                            if (strpos($function_id, '::') !== false) {
                                if ($function_id[0] === '$') {
                                    $function_id = \substr($function_id, 1);
                                }

                                $function_id_parts = explode('&', $function_id);

                                $non_existent_method_ids = [];
                                $has_valid_method = false;

                                foreach ($function_id_parts as $function_id_part) {
                                    [$callable_fq_class_name, $method_name] = explode('::', $function_id_part);

                                    switch ($callable_fq_class_name) {
                                        case 'self':
                                        case 'static':
                                        case 'parent':
                                            $container_class = $statements_analyzer->getFQCLN();

                                            if ($callable_fq_class_name === 'parent') {
                                                $container_class = $statements_analyzer->getParentFQCLN();
                                            }

                                            if (!$container_class) {
                                                continue 2;
                                            }

                                            $callable_fq_class_name = $container_class;
                                    }

                                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                                        $statements_analyzer,
                                        $callable_fq_class_name,
                                        $arg_location,
                                        $context->self,
                                        $context->calling_method_id,
                                        $statements_analyzer->getSuppressedIssues()
                                    ) === false
                                    ) {
                                        return null;
                                    }

                                    $function_id_part = new \Psalm\Internal\MethodIdentifier(
                                        $callable_fq_class_name,
                                        strtolower($method_name)
                                    );

                                    $call_method_id = new \Psalm\Internal\MethodIdentifier(
                                        $callable_fq_class_name,
                                        '__call'
                                    );

                                    if (!$codebase->classOrInterfaceExists($callable_fq_class_name)) {
                                        return null;
                                    }

                                    if (!$codebase->methods->methodExists($function_id_part)
                                        && !$codebase->methods->methodExists($call_method_id)
                                    ) {
                                        $non_existent_method_ids[] = $function_id_part;
                                    } else {
                                        $has_valid_method = true;
                                    }
                                }

                                if (!$has_valid_method && !$param_type->hasString() && !$param_type->hasArray()) {
                                    if (MethodAnalyzer::checkMethodExists(
                                        $codebase,
                                        $non_existent_method_ids[0],
                                        $arg_location,
                                        $statements_analyzer->getSuppressedIssues()
                                    ) === false
                                    ) {
                                        return null;
                                    }
                                }
                            } else {
                                if (!$param_type->hasString()
                                    && !$param_type->hasArray()
                                    && CallAnalyzer::checkFunctionExists(
                                        $statements_analyzer,
                                        $function_id,
                                        $arg_location,
                                        false
                                    ) === false
                                ) {
                                    return null;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!$param_type->isNullable() && $cased_method_id !== 'echo' && $cased_method_id !== 'print') {
            if ($input_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' cannot be null, ' .
                            'null value provided to parameter with type ' . $param_type->getId(),
                        $arg_location,
                        $cased_method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                return null;
            }

            if ($input_type->isNullable() && !$input_type->ignore_nullable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyNullArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' cannot be null, possibly ' .
                            'null value provided',
                        $arg_location,
                        $cased_method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }

        if ($input_type->isFalsable()
            && !$param_type->hasBool()
            && !$param_type->hasScalar()
            && !$input_type->ignore_falsable_issues
            && $cased_method_id !== 'echo'
        ) {
            if (IssueBuffer::accepts(
                new PossiblyFalseArgument(
                    'Argument ' . ($argument_offset + 1) . $method_identifier . ' cannot be false, possibly ' .
                        'false value provided',
                    $arg_location,
                    $cased_method_id
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if (($type_match_found || $input_type->hasMixed())
            && !$function_param->by_ref
            && !($function_param->is_variadic xor $unpack)
            && $cased_method_id !== 'echo'
            && $cased_method_id !== 'print'
            && (!$in_call_map || $context->strict_types)
        ) {
            self::coerceValueAfterGatekeeperArgument(
                $statements_analyzer,
                $input_type,
                $replace_input_type,
                $input_expr,
                $param_type,
                $signature_param_type,
                $context,
                $unpack,
                $unpacked_atomic_array
            );
        }

        return null;
    }

    /**
     * @param Type\Atomic\TKeyedArray|Type\Atomic\TArray|Type\Atomic\TList $unpacked_atomic_array
     */
    private static function coerceValueAfterGatekeeperArgument(
        StatementsAnalyzer $statements_analyzer,
        Type\Union $input_type,
        bool $input_type_changed,
        PhpParser\Node\Expr $input_expr,
        Type\Union $param_type,
        ?Type\Union $signature_param_type,
        Context $context,
        bool $unpack,
        ?Type\Atomic $unpacked_atomic_array
    ) : void {
        if ($param_type->hasMixed()) {
            return;
        }

        if (!$input_type_changed && $param_type->from_docblock && !$input_type->hasMixed()) {
            $input_type = clone $input_type;

            foreach ($param_type->getAtomicTypes() as $param_atomic_type) {
                if ($param_atomic_type instanceof Type\Atomic\TGenericObject) {
                    foreach ($input_type->getAtomicTypes() as $input_atomic_type) {
                        if ($input_atomic_type instanceof Type\Atomic\TGenericObject
                            && $input_atomic_type->value === $param_atomic_type->value
                        ) {
                            foreach ($input_atomic_type->type_params as $i => $type_param) {
                                if ($type_param->isEmpty() && isset($param_atomic_type->type_params[$i])) {
                                    $input_type_changed = true;

                                    /** @psalm-suppress PropertyTypeCoercion */
                                    $input_atomic_type->type_params[$i] = clone $param_atomic_type->type_params[$i];
                                }
                            }
                        }
                    }
                }
            }

            if (!$input_type_changed) {
                return;
            }
        }

        $var_id = ExpressionIdentifier::getVarId(
            $input_expr,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        if ($var_id) {
            $was_cloned = false;

            if ($input_type->isNullable() && !$param_type->isNullable()) {
                $input_type = clone $input_type;
                $was_cloned = true;
                $input_type->removeType('null');
            }

            if ($input_type->getId() === $param_type->getId()) {
                if (!$was_cloned) {
                    $was_cloned = true;
                    $input_type = clone $input_type;
                }

                $input_type->from_docblock = false;

                foreach ($input_type->getAtomicTypes() as $atomic_type) {
                    $atomic_type->from_docblock = false;
                }
            } elseif ($input_type->hasMixed() && $signature_param_type) {
                $was_cloned = true;
                $parent_nodes = $input_type->parent_nodes;
                $by_ref = $input_type->by_ref;
                $input_type = clone $signature_param_type;

                if ($input_type->isNullable()) {
                    $input_type->ignore_nullable_issues = true;
                }

                $input_type->parent_nodes = $parent_nodes;
                $input_type->by_ref = $by_ref;
            }

            if ($context->inside_conditional && !isset($context->assigned_var_ids[$var_id])) {
                $context->assigned_var_ids[$var_id] = false;
            }

            if ($was_cloned) {
                $context->removeVarFromConflictingClauses($var_id, null, $statements_analyzer);
            }

            if ($unpack) {
                if ($unpacked_atomic_array instanceof Type\Atomic\TList) {
                    $unpacked_atomic_array = clone $unpacked_atomic_array;
                    $unpacked_atomic_array->type_param = $input_type;

                    $context->vars_in_scope[$var_id] = new Type\Union([$unpacked_atomic_array]);
                } elseif ($unpacked_atomic_array instanceof Type\Atomic\TArray) {
                    $unpacked_atomic_array = clone $unpacked_atomic_array;
                    /** @psalm-suppress PropertyTypeCoercion */
                    $unpacked_atomic_array->type_params[1] = $input_type;

                    $context->vars_in_scope[$var_id] = new Type\Union([$unpacked_atomic_array]);
                } elseif ($unpacked_atomic_array instanceof Type\Atomic\TKeyedArray
                    && $unpacked_atomic_array->is_list
                ) {
                    $unpacked_atomic_array = $unpacked_atomic_array->getList();
                    $unpacked_atomic_array->type_param = $input_type;

                    $context->vars_in_scope[$var_id] = new Type\Union([$unpacked_atomic_array]);
                } else {
                    $context->vars_in_scope[$var_id] = new Type\Union([
                        new TArray([
                            Type::getInt(),
                            $input_type
                        ]),
                    ]);
                }
            } else {
                $context->vars_in_scope[$var_id] = $input_type;
            }
        }
    }

    private static function processTaintedness(
        StatementsAnalyzer $statements_analyzer,
        string $cased_method_id,
        int $argument_offset,
        CodeLocation $arg_location,
        CodeLocation $function_call_location,
        FunctionLikeParameter $function_param,
        Type\Union $input_type,
        PhpParser\Node\Expr $expr,
        Context $context,
        bool $specialize_taint
    ) : Type\Union {
        $codebase = $statements_analyzer->getCodebase();

        if (!$statements_analyzer->data_flow_graph
            || ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
                && \in_array('TaintedInput', $statements_analyzer->getSuppressedIssues()))
        ) {
            return $input_type;
        }

        if ($function_param->type && $function_param->type->isString() && !$input_type->isString()) {
            $cast_type = CastAnalyzer::castStringAttempt(
                $statements_analyzer,
                $context,
                $input_type,
                $expr,
                false
            );

            $input_type = clone $input_type;
            $input_type->parent_nodes += $cast_type->parent_nodes;
        }

        if ($specialize_taint) {
            $method_node = DataFlowNode::getForMethodArgument(
                $cased_method_id,
                $cased_method_id,
                $argument_offset,
                $function_param->location,
                $function_call_location
            );
        } else {
            $method_node = DataFlowNode::getForMethodArgument(
                $cased_method_id,
                $cased_method_id,
                $argument_offset,
                $function_param->location
            );

            if (strpos($cased_method_id, '::')) {
                [$fq_classlike_name, $cased_method_name] = explode('::', $cased_method_id);
                $method_name = strtolower($cased_method_name);
                $class_storage = $codebase->classlike_storage_provider->get($fq_classlike_name);

                foreach ($class_storage->dependent_classlikes as $dependent_classlike_lc => $_) {
                    $dependent_classlike_storage = $codebase->classlike_storage_provider->get(
                        $dependent_classlike_lc
                    );
                    $new_sink = DataFlowNode::getForMethodArgument(
                        $dependent_classlike_lc . '::' . $method_name,
                        $dependent_classlike_storage->name . '::' . $cased_method_name,
                        $argument_offset,
                        $arg_location,
                        null
                    );

                    $statements_analyzer->data_flow_graph->addNode($new_sink);
                    $statements_analyzer->data_flow_graph->addPath($method_node, $new_sink, 'arg');
                }

                if (isset($class_storage->overridden_method_ids[$method_name])) {
                    foreach ($class_storage->overridden_method_ids[$method_name] as $parent_method_id) {
                        $new_sink = DataFlowNode::getForMethodArgument(
                            (string) $parent_method_id,
                            $codebase->methods->getCasedMethodId($parent_method_id),
                            $argument_offset,
                            $arg_location,
                            null
                        );

                        $statements_analyzer->data_flow_graph->addNode($new_sink);
                        $statements_analyzer->data_flow_graph->addPath($method_node, $new_sink, 'arg');
                    }
                }
            }
        }

        $statements_analyzer->data_flow_graph->addNode($method_node);

        $argument_value_node = DataFlowNode::getForAssignment(
            'call to ' . $cased_method_id,
            $arg_location
        );

        $statements_analyzer->data_flow_graph->addNode($argument_value_node);

        $statements_analyzer->data_flow_graph->addPath($argument_value_node, $method_node, 'arg');

        if ($function_param->sinks && $statements_analyzer->data_flow_graph instanceof TaintFlowGraph) {
            if ($specialize_taint) {
                $sink = TaintSink::getForMethodArgument(
                    $cased_method_id,
                    $cased_method_id,
                    $argument_offset,
                    $function_param->location,
                    $function_call_location
                );
            } else {
                $sink = TaintSink::getForMethodArgument(
                    $cased_method_id,
                    $cased_method_id,
                    $argument_offset,
                    $function_param->location
                );
            }

            $sink->taints = $function_param->sinks;

            $statements_analyzer->data_flow_graph->addSink($sink);
        }

        foreach ($input_type->parent_nodes as $parent_node) {
            $statements_analyzer->data_flow_graph->addNode($method_node);
            $statements_analyzer->data_flow_graph->addPath($parent_node, $argument_value_node, 'arg');
        }

        if ($function_param->assert_untainted) {
            $input_type = clone $input_type;
            $input_type->parent_nodes = [];
        }

        return $input_type;
    }
}
