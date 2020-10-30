<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class ArrayUniqueReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['array_unique'];
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

        $first_arg = $call_args[0]->value ?? null;

        $first_arg_array = $first_arg
            && ($first_arg_type = $statements_source->node_data->getType($first_arg))
            && $first_arg_type->hasType('array')
            && ($array_atomic_type = $first_arg_type->getAtomicTypes()['array'])
            && ($array_atomic_type instanceof Type\Atomic\TArray
                || $array_atomic_type instanceof Type\Atomic\TKeyedArray
                || $array_atomic_type instanceof Type\Atomic\TList)
        ? $array_atomic_type
        : null;

        if (!$first_arg_array) {
            return Type::getArray();
        }

        if ($first_arg_array instanceof Type\Atomic\TArray) {
            $first_arg_array = clone $first_arg_array;

            if ($first_arg_array instanceof Type\Atomic\TNonEmptyArray) {
                $first_arg_array->count = null;
            }

            return new Type\Union([$first_arg_array]);
        }

        if ($first_arg_array instanceof Type\Atomic\TList) {
            if ($first_arg_array instanceof Type\Atomic\TNonEmptyList) {
                return new Type\Union([
                    new Type\Atomic\TNonEmptyArray([
                        Type::getInt(),
                        clone $first_arg_array->type_param
                    ])
                ]);
            }

            return new Type\Union([
                new Type\Atomic\TArray([
                    Type::getInt(),
                    clone $first_arg_array->type_param
                ])
            ]);
        }

        return new Type\Union([$first_arg_array->getGenericArrayType()]);
    }
}
