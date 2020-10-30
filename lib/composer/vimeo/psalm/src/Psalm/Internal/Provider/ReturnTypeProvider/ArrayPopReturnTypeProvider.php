<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class ArrayPopReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['array_pop', 'array_shift'];
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
            && !$first_arg_type->hasMixed()
            && ($array_atomic_type = $first_arg_type->getAtomicTypes()['array'])
            && ($array_atomic_type instanceof Type\Atomic\TArray
                || $array_atomic_type instanceof Type\Atomic\TKeyedArray
                || $array_atomic_type instanceof Type\Atomic\TList)
        ? $array_atomic_type
        : null;

        if (!$first_arg_array) {
            return Type::getMixed();
        }

        $nullable = false;

        if ($first_arg_array instanceof Type\Atomic\TArray) {
            $value_type = clone $first_arg_array->type_params[1];

            if ($value_type->isEmpty()) {
                return Type::getNull();
            }

            if (!$first_arg_array instanceof Type\Atomic\TNonEmptyArray) {
                $nullable = true;
            }
        } elseif ($first_arg_array instanceof Type\Atomic\TList) {
            $value_type = clone $first_arg_array->type_param;

            if (!$first_arg_array instanceof Type\Atomic\TNonEmptyList) {
                $nullable = true;
            }
        } else {
            // special case where we know the type of the first element
            if ($function_id === 'array_shift' && $first_arg_array->is_list && isset($first_arg_array->properties[0])) {
                $value_type = clone $first_arg_array->properties[0];
            } else {
                $value_type = $first_arg_array->getGenericValueType();

                if (!$first_arg_array->sealed && !$first_arg_array->previous_value_type) {
                    $nullable = true;
                }
            }
        }

        if ($nullable) {
            $value_type->addType(new Type\Atomic\TNull);

            $codebase = $statements_source->getCodebase();

            if ($codebase->config->ignore_internal_nullable_issues) {
                $value_type->ignore_nullable_issues = true;
            }
        }

        return $value_type;
    }
}
