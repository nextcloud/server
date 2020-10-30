<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class ArraySliceReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['array_slice'];
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

        $dont_preserve_int_keys = !isset($call_args[3]->value)
            || (($third_arg_type = $statements_source->node_data->getType($call_args[3]->value))
                && ((string) $third_arg_type === 'false'));

        $already_cloned = false;

        if ($first_arg_array instanceof Type\Atomic\TKeyedArray) {
            $already_cloned = true;
            $first_arg_array = $first_arg_array->getGenericArrayType();
        }

        if ($first_arg_array instanceof Type\Atomic\TArray) {
            if (!$already_cloned) {
                $first_arg_array = clone $first_arg_array;
            }
            $array_type = new Type\Atomic\TArray($first_arg_array->type_params);
        } else {
            $array_type = new Type\Atomic\TArray([Type::getInt(), clone $first_arg_array->type_param]);
        }

        if ($dont_preserve_int_keys && $array_type->type_params[0]->isInt()) {
            $array_type = new Type\Atomic\TList($array_type->type_params[1]);
        }

        return new Type\Union([$array_type]);
    }
}
