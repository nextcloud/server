<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class ExplodeReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['explode'];
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

        if (\count($call_args) >= 2) {
            $second_arg_type = $statements_source->node_data->getType($call_args[1]->value);

            $inner_type = new Type\Union([
                $second_arg_type && $second_arg_type->hasLowercaseString()
                    ? new Type\Atomic\TLowercaseString()
                    : new Type\Atomic\TString
            ]);

            $can_return_empty = isset($call_args[2])
                && (
                    !$call_args[2]->value instanceof PhpParser\Node\Scalar\LNumber
                    || $call_args[2]->value->value < 0
                );

            if ($call_args[0]->value instanceof PhpParser\Node\Scalar\String_) {
                if ($call_args[0]->value->value === '') {
                    return Type::getFalse();
                }

                return new Type\Union([
                    $can_return_empty
                        ? new Type\Atomic\TList($inner_type)
                        : new Type\Atomic\TNonEmptyList($inner_type)
                ]);
            } elseif (($first_arg_type = $statements_source->node_data->getType($call_args[0]->value))
                && $first_arg_type->hasString()
            ) {
                $falsable_array = new Type\Union([
                    $can_return_empty
                        ? new Type\Atomic\TList($inner_type)
                        : new Type\Atomic\TNonEmptyList($inner_type),
                    new Type\Atomic\TFalse
                ]);

                if ($statements_source->getCodebase()->config->ignore_internal_falsable_issues) {
                    $falsable_array->ignore_falsable_issues = true;
                }

                return $falsable_array;
            }
        }

        return Type::getMixed();
    }
}
