<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class ArrayColumnReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['array_column'];
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

        $row_shape = null;
        $input_array_not_empty = false;

        // calculate row shape
        if (($first_arg_type = $statements_source->node_data->getType($call_args[0]->value))
            && $first_arg_type->isSingle()
            && $first_arg_type->hasArray()
        ) {
            $input_array = $first_arg_type->getAtomicTypes()['array'];
            if ($input_array instanceof Type\Atomic\TKeyedArray) {
                $row_type = $input_array->getGenericArrayType()->type_params[1];
                if ($row_type->isSingle() && $row_type->hasArray()) {
                    $row_shape = $row_type->getAtomicTypes()['array'];
                }
            } elseif ($input_array instanceof Type\Atomic\TArray) {
                $row_type = $input_array->type_params[1];
                if ($row_type->isSingle() && $row_type->hasArray()) {
                    $row_shape = $row_type->getAtomicTypes()['array'];
                }
            } elseif ($input_array instanceof Type\Atomic\TList) {
                $row_type = $input_array->type_param;
                if ($row_type->isSingle() && $row_type->hasArray()) {
                    $row_shape = $row_type->getAtomicTypes()['array'];
                }
            }

            $input_array_not_empty = $input_array instanceof Type\Atomic\TNonEmptyList ||
                $input_array instanceof Type\Atomic\TNonEmptyArray ||
                $input_array instanceof Type\Atomic\TKeyedArray;
        }

        $value_column_name = null;
        // calculate value column name
        if (($second_arg_type = $statements_source->node_data->getType($call_args[1]->value))) {
            if ($second_arg_type->isSingleIntLiteral()) {
                $value_column_name = $second_arg_type->getSingleIntLiteral()->value;
            } elseif ($second_arg_type->isSingleStringLiteral()) {
                $value_column_name = $second_arg_type->getSingleStringLiteral()->value;
            }
        }

        $key_column_name = null;
        $third_arg_type = null;
        // calculate key column name
        if (isset($call_args[2])) {
            $third_arg_type = $statements_source->node_data->getType($call_args[2]->value);

            if ($third_arg_type) {
                if ($third_arg_type->isSingleIntLiteral()) {
                    $key_column_name = $third_arg_type->getSingleIntLiteral()->value;
                } elseif ($third_arg_type->isSingleStringLiteral()) {
                    $key_column_name = $third_arg_type->getSingleStringLiteral()->value;
                }
            }
        }

        $result_key_type = Type::getArrayKey();
        $result_element_type = null;
        $have_at_least_one_res = false;
        // calculate results
        if ($row_shape instanceof Type\Atomic\TKeyedArray) {
            if ((null !== $value_column_name) && isset($row_shape->properties[$value_column_name])) {
                if ($input_array_not_empty) {
                    $have_at_least_one_res = true;
                }
                $result_element_type = $row_shape->properties[$value_column_name];
            } else {
                $result_element_type = Type::getMixed();
            }

            if ((null !== $key_column_name) && isset($row_shape->properties[$key_column_name])) {
                $result_key_type = $row_shape->properties[$key_column_name];
            }
        }

        if (isset($call_args[2]) && (string)$third_arg_type !== 'null') {
            $type = $have_at_least_one_res ?
                new Type\Atomic\TNonEmptyArray([$result_key_type, $result_element_type ?? Type::getMixed()])
                : new Type\Atomic\TArray([$result_key_type, $result_element_type ?? Type::getMixed()]);
        } else {
            $type = $have_at_least_one_res ?
                new Type\Atomic\TNonEmptyList($result_element_type ?? Type::getMixed())
                : new Type\Atomic\TList($result_element_type ?? Type::getMixed());
        }

        return new Type\Union([$type]);
    }
}
