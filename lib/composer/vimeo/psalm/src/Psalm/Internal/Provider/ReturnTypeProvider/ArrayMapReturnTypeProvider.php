<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use function array_map;
use function count;
use function explode;
use function in_array;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Type\ArrayType;
use Psalm\StatementsSource;
use Psalm\Type;
use function strpos;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;

class ArrayMapReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['array_map'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     */
    public static function getFunctionReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union {
        if (!$statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
            return Type::getMixed();
        }

        $function_call_arg = $call_args[0] ?? null;

        $function_call_type = $function_call_arg
            ? $statements_source->node_data->getType($function_call_arg->value)
            : null;

        if ($function_call_type && $function_call_type->isNull()) {
            \array_shift($call_args);

            $array_arg_types = [];

            foreach ($call_args as $call_arg) {
                $call_arg_type = $statements_source->node_data->getType($call_arg->value);

                if ($call_arg_type) {
                    $array_arg_types[] = clone $call_arg_type;
                } else {
                    $array_arg_types[] = Type::getMixed();
                    break;
                }
            }

            if ($array_arg_types) {
                return new Type\Union([new Type\Atomic\TKeyedArray($array_arg_types)]);
            }

            return Type::getArray();
        }

        $array_arg = $call_args[1] ?? null;

        if (!$array_arg) {
            return Type::getArray();
        }

        $array_arg_atomic_type = null;
        $array_arg_type = null;

        if ($array_arg_union_type = $statements_source->node_data->getType($array_arg->value)) {
            $arg_types = $array_arg_union_type->getAtomicTypes();

            if (isset($arg_types['array'])) {
                $array_arg_atomic_type = $arg_types['array'];
                $array_arg_type = ArrayType::infer($array_arg_atomic_type);
            }
        }

        $generic_key_type = null;
        $mapping_return_type = null;

        if ($function_call_arg && $function_call_type) {
            if (count($call_args) === 2) {
                $generic_key_type = $array_arg_type->key ?? Type::getArrayKey();
            } else {
                $generic_key_type = Type::getInt();
            }

            if ($closure_types = $function_call_type->getClosureTypes()) {
                $closure_atomic_type = \reset($closure_types);

                $closure_return_type = $closure_atomic_type->return_type ?: Type::getMixed();

                if ($closure_return_type->isVoid()) {
                    $closure_return_type = Type::getNull();
                }

                $mapping_return_type = clone $closure_return_type;
            } elseif ($function_call_arg->value instanceof PhpParser\Node\Scalar\String_
                || $function_call_arg->value instanceof PhpParser\Node\Expr\Array_
                || $function_call_arg->value instanceof PhpParser\Node\Expr\BinaryOp\Concat
            ) {
                $mapping_function_ids = CallAnalyzer::getFunctionIdsFromCallableArg(
                    $statements_source,
                    $function_call_arg->value
                );

                if ($mapping_function_ids) {
                    $mapping_return_type = self::getReturnTypeFromMappingIds(
                        $statements_source,
                        $mapping_function_ids,
                        $context,
                        $function_call_arg,
                        \array_slice($call_args, 1)
                    );
                }

                if ($function_call_arg->value instanceof PhpParser\Node\Expr\Array_
                    && isset($function_call_arg->value->items[0])
                    && isset($function_call_arg->value->items[1])
                    && $function_call_arg->value->items[1]->value instanceof PhpParser\Node\Scalar\String_
                    && $function_call_arg->value->items[0]->value instanceof PhpParser\Node\Expr\Variable
                    && ($variable_type
                        = $statements_source->node_data->getType($function_call_arg->value->items[0]->value))
                ) {
                    $fake_method_call = null;

                    foreach ($variable_type->getAtomicTypes() as $variable_atomic_type) {
                        if ($variable_atomic_type instanceof Type\Atomic\TTemplateParam
                            || $variable_atomic_type instanceof Type\Atomic\TTemplateParamClass
                        ) {
                            $fake_method_call = new PhpParser\Node\Expr\StaticCall(
                                $function_call_arg->value->items[0]->value,
                                $function_call_arg->value->items[1]->value->value,
                                []
                            );
                        } elseif ($variable_atomic_type instanceof Type\Atomic\TTemplateParamClass) {
                            $fake_method_call = new PhpParser\Node\Expr\StaticCall(
                                $function_call_arg->value->items[0]->value,
                                $function_call_arg->value->items[1]->value->value,
                                []
                            );
                        }
                    }

                    if ($fake_method_call) {
                        $fake_method_return_type = self::executeFakeCall(
                            $statements_source,
                            $fake_method_call,
                            $context
                        );

                        if ($fake_method_return_type) {
                            $mapping_return_type = $fake_method_return_type;
                        }
                    }
                }
            }
        }

        if ($mapping_return_type && $generic_key_type) {
            if ($array_arg_atomic_type instanceof Type\Atomic\TKeyedArray && count($call_args) === 2) {
                $atomic_type = new Type\Atomic\TKeyedArray(
                    array_map(
                        /**
                        * @return Type\Union
                        */
                        function (Type\Union $_) use ($mapping_return_type): Type\Union {
                            return clone $mapping_return_type;
                        },
                        $array_arg_atomic_type->properties
                    )
                );
                $atomic_type->is_list = $array_arg_atomic_type->is_list;
                $atomic_type->sealed = $array_arg_atomic_type->sealed;

                return new Type\Union([$atomic_type]);
            }

            if ($array_arg_atomic_type instanceof Type\Atomic\TList
                || count($call_args) !== 2
            ) {
                if ($array_arg_atomic_type instanceof Type\Atomic\TNonEmptyList) {
                    return new Type\Union([
                        new Type\Atomic\TNonEmptyList(
                            $mapping_return_type
                        ),
                    ]);
                }

                return new Type\Union([
                    new Type\Atomic\TList(
                        $mapping_return_type
                    ),
                ]);
            }

            if ($array_arg_atomic_type instanceof Type\Atomic\TNonEmptyArray) {
                return new Type\Union([
                    new Type\Atomic\TNonEmptyArray([
                        $generic_key_type,
                        $mapping_return_type,
                    ]),
                ]);
            }

            return new Type\Union([
                new Type\Atomic\TArray([
                    $generic_key_type,
                    $mapping_return_type,
                ])
            ]);
        }

        return count($call_args) === 2 && !($array_arg_type->is_list ?? false)
            ? new Type\Union([
                new Type\Atomic\TArray([
                    $array_arg_type->key ?? Type::getArrayKey(),
                    Type::getMixed(),
                ])
            ])
            : Type::getList();
    }

    /**
     * @param-out array<string, array<array<string>>>|null $assertions
     */
    private static function executeFakeCall(
        \Psalm\Internal\Analyzer\StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $fake_call,
        Context $context,
        ?array &$assertions = null
    ) : ?Type\Union {
        $old_data_provider = $statements_analyzer->node_data;

        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        $suppressed_issues = $statements_analyzer->getSuppressedIssues();

        if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['PossiblyInvalidMethodCall']);
        }

        if (!in_array('MixedArrayOffset', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['MixedArrayOffset']);
        }

        $was_inside_call = $context->inside_call;

        $context->inside_call = true;

        if ($fake_call instanceof PhpParser\Node\Expr\StaticCall) {
            \Psalm\Internal\Analyzer\Statements\Expression\Call\StaticCallAnalyzer::analyze(
                $statements_analyzer,
                $fake_call,
                $context
            );
        } elseif ($fake_call instanceof PhpParser\Node\Expr\MethodCall) {
            \Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer::analyze(
                $statements_analyzer,
                $fake_call,
                $context
            );
        } elseif ($fake_call instanceof PhpParser\Node\Expr\FuncCall) {
            \Psalm\Internal\Analyzer\Statements\Expression\Call\FunctionCallAnalyzer::analyze(
                $statements_analyzer,
                $fake_call,
                $context
            );
        } else {
            throw new \UnexpectedValueException('UnrecognizedCall');
        }

        $codebase = $statements_analyzer->getCodebase();

        if ($assertions !== null) {
            $assertions = AssertionFinder::scrapeAssertions(
                $fake_call,
                null,
                $statements_analyzer,
                $codebase
            );
        }

        $context->inside_call = $was_inside_call;

        if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['PossiblyInvalidMethodCall']);
        }

        if (!in_array('MixedArrayOffset', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['MixedArrayOffset']);
        }

        $return_type = $statements_analyzer->node_data->getType($fake_call) ?: null;

        $statements_analyzer->node_data = $old_data_provider;

        return $return_type;
    }

    /**
     * @param non-empty-array<string> $mapping_function_ids
     * @param array<PhpParser\Node\Arg> $array_args
     * @param-out array<string, array<array<string>>>|null $assertions
     */
    public static function getReturnTypeFromMappingIds(
        \Psalm\Internal\Analyzer\StatementsAnalyzer $statements_source,
        array $mapping_function_ids,
        Context $context,
        PhpParser\Node\Arg $function_call_arg,
        array $array_args,
        ?array &$assertions = null
    ) : Type\Union {
        $mapping_return_type = null;

        $codebase = $statements_source->getCodebase();

        foreach ($mapping_function_ids as $mapping_function_id) {
            $mapping_function_id_parts = explode('&', $mapping_function_id);

            foreach ($mapping_function_id_parts as $mapping_function_id_part) {
                $fake_args = [];

                foreach ($array_args as $array_arg) {
                    $fake_args[] = new PhpParser\Node\Arg(
                        new PhpParser\Node\Expr\ArrayDimFetch(
                            $array_arg->value,
                            new PhpParser\Node\Expr\Variable(
                                '__fake_offset_var__',
                                $array_arg->value->getAttributes()
                            ),
                            $array_arg->value->getAttributes()
                        ),
                        false,
                        false,
                        $array_arg->getAttributes()
                    );
                }

                if (strpos($mapping_function_id_part, '::') !== false) {
                    $is_instance = false;

                    if ($mapping_function_id_part[0] === '$') {
                        $mapping_function_id_part = \substr($mapping_function_id_part, 1);
                        $is_instance = true;
                    }

                    $method_id_parts = explode('::', $mapping_function_id_part);
                    [$callable_fq_class_name, $callable_method_name] = $method_id_parts;

                    if ($is_instance) {
                        $fake_method_call = new PhpParser\Node\Expr\MethodCall(
                            new PhpParser\Node\Expr\Variable(
                                '__fake_method_call_var__',
                                $function_call_arg->getAttributes()
                            ),
                            new PhpParser\Node\Identifier(
                                $callable_method_name,
                                $function_call_arg->getAttributes()
                            ),
                            $fake_args,
                            $function_call_arg->getAttributes()
                        );

                        $context->vars_in_scope['$__fake_offset_var__'] = Type::getMixed();
                        $context->vars_in_scope['$__fake_method_call_var__'] = new Type\Union([
                            new Type\Atomic\TNamedObject($callable_fq_class_name)
                        ]);

                        $fake_method_return_type = self::executeFakeCall(
                            $statements_source,
                            $fake_method_call,
                            $context,
                            $assertions
                        );

                        unset($context->vars_in_scope['$__fake_offset_var__']);
                        unset($context->vars_in_scope['$__method_call_var__']);
                    } else {
                        $fake_method_call = new PhpParser\Node\Expr\StaticCall(
                            new PhpParser\Node\Name\FullyQualified(
                                $callable_fq_class_name,
                                $function_call_arg->getAttributes()
                            ),
                            new PhpParser\Node\Identifier(
                                $callable_method_name,
                                $function_call_arg->getAttributes()
                            ),
                            $fake_args,
                            $function_call_arg->getAttributes()
                        );

                        $context->vars_in_scope['$__fake_offset_var__'] = Type::getMixed();

                        $fake_method_return_type = self::executeFakeCall(
                            $statements_source,
                            $fake_method_call,
                            $context,
                            $assertions
                        );

                        unset($context->vars_in_scope['$__fake_offset_var__']);
                    }

                    $function_id_return_type = $fake_method_return_type ?: Type::getMixed();
                } else {
                    $fake_function_call = new PhpParser\Node\Expr\FuncCall(
                        new PhpParser\Node\Name\FullyQualified(
                            $mapping_function_id_part,
                            $function_call_arg->getAttributes()
                        ),
                        $fake_args,
                        $function_call_arg->getAttributes()
                    );

                    $context->vars_in_scope['$__fake_offset_var__'] = Type::getMixed();

                    $fake_function_return_type = self::executeFakeCall(
                        $statements_source,
                        $fake_function_call,
                        $context,
                        $assertions
                    );

                    unset($context->vars_in_scope['$__fake_offset_var__']);

                    $function_id_return_type = $fake_function_return_type ?: Type::getMixed();
                }
            }

            if (!$mapping_return_type) {
                $mapping_return_type = $function_id_return_type;
            } else {
                $mapping_return_type = Type::combineUnionTypes(
                    $function_id_return_type,
                    $mapping_return_type,
                    $codebase
                );
            }
        }

        return $mapping_return_type;
    }
}
