<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class ArrayRandReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['array_rand'];
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

        $first_arg = isset($call_args[0]->value) ? $call_args[0]->value : null;
        $second_arg = isset($call_args[1]->value) ? $call_args[1]->value : null;

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
            return Type::getMixed();
        }

        if ($first_arg_array instanceof Type\Atomic\TArray) {
            $key_type = clone $first_arg_array->type_params[0];
        } elseif ($first_arg_array instanceof Type\Atomic\TList) {
            $key_type = Type::getInt();
        } else {
            $key_type = $first_arg_array->getGenericKeyType();
        }

        if (!$second_arg
            || ($second_arg instanceof PhpParser\Node\Scalar\LNumber && $second_arg->value === 1)
        ) {
            return $key_type;
        }

        $arr_type = new Type\Union([
            new Type\Atomic\TList(
                $key_type
            ),
        ]);

        if ($second_arg instanceof PhpParser\Node\Scalar\LNumber) {
            return $arr_type;
        }

        return Type::combineUnionTypes($key_type, $arr_type);
    }
}
