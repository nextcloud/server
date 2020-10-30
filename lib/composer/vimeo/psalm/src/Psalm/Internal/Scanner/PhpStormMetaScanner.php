<?php
namespace Psalm\Internal\Scanner;

use PhpParser;
use Psalm\Context;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Type;
use function strtolower;
use function implode;
use function is_string;
use function strpos;
use function str_replace;

/**
 * @internal
 */
class PhpStormMetaScanner
{
    /**
     * @param  array<PhpParser\Node\Arg> $args
     */
    public static function handleOverride(array $args, Codebase $codebase): void
    {
        $identifier = $args[0]->value;

        if (!$args[1]->value instanceof PhpParser\Node\Expr\FuncCall
            || !$args[1]->value->name instanceof PhpParser\Node\Name
        ) {
            return;
        }

        $map = [];

        if ($args[1]->value->name->parts === ['map']
            && $args[1]->value->args
            && $args[1]->value->args[0]->value instanceof PhpParser\Node\Expr\Array_
        ) {
            foreach ($args[1]->value->args[0]->value->items as $array_item) {
                if ($array_item
                    && $array_item->key instanceof PhpParser\Node\Scalar\String_
                ) {
                    if ($array_item->value instanceof PhpParser\Node\Expr\ClassConstFetch
                        && $array_item->value->class instanceof PhpParser\Node\Name\FullyQualified
                        && $array_item->value->name instanceof PhpParser\Node\Identifier
                        && strtolower($array_item->value->name->name)
                    ) {
                        $map[$array_item->key->value] = new Type\Union([
                            new Type\Atomic\TNamedObject(implode('\\', $array_item->value->class->parts))
                        ]);
                    } elseif ($array_item->value instanceof PhpParser\Node\Scalar\String_) {
                        $map[$array_item->key->value] = $array_item->value->value;
                    }
                }
            }
        }

        $type_offset = null;

        if ($args[1]->value->name->parts === ['type']
            && $args[1]->value->args
            && $args[1]->value->args[0]->value instanceof PhpParser\Node\Scalar\LNumber
        ) {
            $type_offset = $args[1]->value->args[0]->value->value;
        }

        $element_type_offset = null;

        if ($args[1]->value->name->parts === ['elementType']
            && $args[1]->value->args
            && $args[1]->value->args[0]->value instanceof PhpParser\Node\Scalar\LNumber
        ) {
            $element_type_offset = $args[1]->value->args[0]->value->value;
        }

        if ($identifier instanceof PhpParser\Node\Expr\StaticCall
            && $identifier->class instanceof PhpParser\Node\Name\FullyQualified
            && $identifier->name instanceof PhpParser\Node\Identifier
            && $identifier->args
            && $identifier->args[0]->value instanceof PhpParser\Node\Scalar\LNumber
        ) {
            $meta_fq_classlike_name = implode('\\', $identifier->class->parts);

            $meta_method_name = strtolower($identifier->name->name);

            if ($map) {
                $offset = $identifier->args[0]->value->value;

                $codebase->methods->return_type_provider->registerClosure(
                    $meta_fq_classlike_name,
                    /**
                     * @param array<PhpParser\Node\Arg> $call_args
                     */
                    function (
                        \Psalm\StatementsSource $statements_analyzer,
                        string $fq_classlike_name,
                        string $method_name,
                        array $call_args,
                        Context $_context,
                        CodeLocation $_code_location
                    ) use (
                        $map,
                        $offset,
                        $meta_fq_classlike_name,
                        $meta_method_name
                    ): ?Type\Union {
                        if (!$statements_analyzer instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
                            return Type::getMixed();
                        }

                        if ($meta_method_name !== $method_name
                            || $meta_fq_classlike_name !== $fq_classlike_name
                        ) {
                            return null;
                        }

                        if (isset($call_args[$offset]->value)
                            && ($call_arg_type = $statements_analyzer->node_data->getType($call_args[$offset]->value))
                            && $call_arg_type->isSingleStringLiteral()
                        ) {
                            $offset_arg_value = $call_arg_type->getSingleStringLiteral()->value;

                            if ($mapped_type = $map[$offset_arg_value] ?? null) {
                                if ($mapped_type instanceof Type\Union) {
                                    return clone $mapped_type;
                                }
                            }

                            if (($mapped_type = $map[''] ?? null) && is_string($mapped_type)) {
                                if (strpos($mapped_type, '@') !== false) {
                                    $mapped_type = str_replace('@', $offset_arg_value, $mapped_type);

                                    if (strpos($mapped_type, '.') === false) {
                                        return new Type\Union([
                                            new Type\Atomic\TNamedObject($mapped_type)
                                        ]);
                                    }
                                }
                            }
                        }

                        return null;
                    }
                );
            } elseif ($type_offset !== null) {
                $codebase->methods->return_type_provider->registerClosure(
                    $meta_fq_classlike_name,
                    /**
                     * @param array<PhpParser\Node\Arg> $call_args
                     */
                    function (
                        \Psalm\StatementsSource $statements_analyzer,
                        string $fq_classlike_name,
                        string $method_name,
                        array $call_args,
                        Context $_context,
                        CodeLocation $_code_location
                    ) use (
                        $map,
                        $type_offset,
                        $meta_fq_classlike_name,
                        $meta_method_name
                    ): ?Type\Union {
                        if (!$statements_analyzer instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
                            return Type::getMixed();
                        }

                        if ($meta_method_name !== $method_name
                            || $meta_fq_classlike_name !== $fq_classlike_name
                        ) {
                            return null;
                        }

                        if (isset($call_args[$type_offset]->value)
                            && ($call_arg_type
                                = $statements_analyzer->node_data->getType($call_args[$type_offset]->value))
                        ) {
                            return clone $call_arg_type;
                        }

                        return null;
                    }
                );
            } elseif ($element_type_offset !== null) {
                $codebase->methods->return_type_provider->registerClosure(
                    $meta_fq_classlike_name,
                    /**
                     * @param array<PhpParser\Node\Arg> $call_args
                     */
                    function (
                        \Psalm\StatementsSource $statements_analyzer,
                        string $fq_classlike_name,
                        string $method_name,
                        array $call_args,
                        Context $_context,
                        CodeLocation $_code_location
                    ) use (
                        $map,
                        $element_type_offset,
                        $meta_fq_classlike_name,
                        $meta_method_name
                    ): ?Type\Union {
                        if (!$statements_analyzer instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
                            return Type::getMixed();
                        }

                        if ($meta_method_name !== $method_name
                            || $meta_fq_classlike_name !== $fq_classlike_name
                        ) {
                            return null;
                        }

                        if (isset($call_args[$element_type_offset]->value)
                            && ($call_arg_type
                                = $statements_analyzer->node_data->getType($call_args[$element_type_offset]->value))
                            && $call_arg_type->hasArray()
                        ) {
                            /**
                             * @psalm-suppress PossiblyUndefinedStringArrayOffset
                             * @var Type\Atomic\TArray|Type\Atomic\TKeyedArray|Type\Atomic\TList
                             */
                            $array_atomic_type = $call_arg_type->getAtomicTypes()['array'];

                            if ($array_atomic_type instanceof Type\Atomic\TKeyedArray) {
                                return $array_atomic_type->getGenericValueType();
                            }

                            if ($array_atomic_type instanceof Type\Atomic\TList) {
                                return $array_atomic_type->type_param;
                            }

                            return clone $array_atomic_type->type_params[1];
                        }

                        return null;
                    }
                );
            }
        }

        if ($identifier instanceof PhpParser\Node\Expr\FuncCall
            && $identifier->name instanceof PhpParser\Node\Name\FullyQualified
            && $identifier->args
            && $identifier->args[0]->value instanceof PhpParser\Node\Scalar\LNumber
        ) {
            $function_id = implode('\\', $identifier->name->parts);

            if ($map) {
                $offset = $identifier->args[0]->value->value;

                $codebase->functions->return_type_provider->registerClosure(
                    $function_id,
                    /**
                     * @param non-empty-string $function_id
                     * @param array<PhpParser\Node\Arg> $call_args
                     */
                    function (
                        \Psalm\StatementsSource $statements_analyzer,
                        string $function_id,
                        array $call_args,
                        Context $_context,
                        CodeLocation $_code_location
                    ) use (
                        $map,
                        $offset
                    ) : Type\Union {
                        if (!$statements_analyzer instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
                            return Type::getMixed();
                        }

                        if (isset($call_args[$offset]->value)
                            && ($call_arg_type
                                = $statements_analyzer->node_data->getType($call_args[$offset]->value))
                            && $call_arg_type->isSingleStringLiteral()
                        ) {
                            $offset_arg_value = $call_arg_type->getSingleStringLiteral()->value;

                            if ($mapped_type = $map[$offset_arg_value] ?? null) {
                                if ($mapped_type instanceof Type\Union) {
                                    return clone $mapped_type;
                                }
                            }

                            if (($mapped_type = $map[''] ?? null) && is_string($mapped_type)) {
                                if (strpos($mapped_type, '@') !== false) {
                                    $mapped_type = str_replace('@', $offset_arg_value, $mapped_type);

                                    if (strpos($mapped_type, '.') === false) {
                                        return new Type\Union([
                                            new Type\Atomic\TNamedObject($mapped_type)
                                        ]);
                                    }
                                }
                            }
                        }

                        $storage = $statements_analyzer->getCodebase()->functions->getStorage(
                            $statements_analyzer,
                            strtolower($function_id)
                        );

                        return $storage->return_type ?: Type::getMixed();
                    }
                );
            } elseif ($type_offset !== null) {
                $codebase->functions->return_type_provider->registerClosure(
                    $function_id,
                    /**
                     * @param non-empty-string $function_id
                     * @param array<PhpParser\Node\Arg> $call_args
                     */
                    function (
                        \Psalm\StatementsSource $statements_analyzer,
                        string $function_id,
                        array $call_args,
                        Context $_context,
                        CodeLocation $_code_location
                    ) use (
                        $map,
                        $type_offset
                    ) : Type\Union {
                        if (!$statements_analyzer instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
                            return Type::getMixed();
                        }

                        if (isset($call_args[$type_offset]->value)
                            && ($call_arg_type
                                = $statements_analyzer->node_data->getType($call_args[$type_offset]->value))
                        ) {
                            return clone $call_arg_type;
                        }

                        $storage = $statements_analyzer->getCodebase()->functions->getStorage(
                            $statements_analyzer,
                            strtolower($function_id)
                        );

                        return $storage->return_type ?: Type::getMixed();
                    }
                );
            } elseif ($element_type_offset !== null) {
                $codebase->functions->return_type_provider->registerClosure(
                    $function_id,
                    /**
                     * @param non-empty-string $function_id
                     * @param array<PhpParser\Node\Arg> $call_args
                     */
                    function (
                        \Psalm\StatementsSource $statements_analyzer,
                        string $function_id,
                        array $call_args,
                        Context $_context,
                        CodeLocation $_code_location
                    ) use (
                        $map,
                        $element_type_offset
                    ) : Type\Union {
                        if (!$statements_analyzer instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
                            return Type::getMixed();
                        }

                        if (isset($call_args[$element_type_offset]->value)
                            && ($call_arg_type
                                = $statements_analyzer->node_data->getType($call_args[$element_type_offset]->value))
                            && $call_arg_type->hasArray()
                        ) {
                            /**
                             * @psalm-suppress PossiblyUndefinedStringArrayOffset
                             * @var Type\Atomic\TArray|Type\Atomic\TKeyedArray|Type\Atomic\TList
                             */
                            $array_atomic_type = $call_arg_type->getAtomicTypes()['array'];

                            if ($array_atomic_type instanceof Type\Atomic\TKeyedArray) {
                                return $array_atomic_type->getGenericValueType();
                            }

                            if ($array_atomic_type instanceof Type\Atomic\TList) {
                                return $array_atomic_type->type_param;
                            }

                            return clone $array_atomic_type->type_params[1];
                        }

                        $storage = $statements_analyzer->getCodebase()->functions->getStorage(
                            $statements_analyzer,
                            strtolower($function_id)
                        );

                        return $storage->return_type ?: Type::getMixed();
                    }
                );
            }
        }
    }
}
