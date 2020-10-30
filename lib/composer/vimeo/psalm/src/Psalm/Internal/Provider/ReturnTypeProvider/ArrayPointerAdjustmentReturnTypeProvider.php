<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class ArrayPointerAdjustmentReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['current', 'next', 'prev', 'reset', 'end'];
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
            return Type::getMixed();
        }

        if ($first_arg_array instanceof Type\Atomic\TArray) {
            $value_type = clone $first_arg_array->type_params[1];
            $definitely_has_items = $first_arg_array instanceof Type\Atomic\TNonEmptyArray;
        } elseif ($first_arg_array instanceof Type\Atomic\TList) {
            $value_type = clone $first_arg_array->type_param;
            $definitely_has_items = $first_arg_array instanceof Type\Atomic\TNonEmptyList;
        } else {
            $value_type = $first_arg_array->getGenericValueType();
            $definitely_has_items = $first_arg_array->getGenericArrayType() instanceof Type\Atomic\TNonEmptyArray;
        }

        if ($value_type->isEmpty()) {
            $value_type = Type::getFalse();
        } elseif (($function_id !== 'reset' && $function_id !== 'end') || !$definitely_has_items) {
            $value_type->addType(new Type\Atomic\TFalse);

            $codebase = $statements_source->getCodebase();

            if ($codebase->config->ignore_internal_falsable_issues) {
                $value_type->ignore_falsable_issues = true;
            }
        }

        return $value_type;
    }
}
