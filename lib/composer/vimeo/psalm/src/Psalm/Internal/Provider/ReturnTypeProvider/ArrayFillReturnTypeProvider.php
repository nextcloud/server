<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class ArrayFillReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['array_fill'];
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

        $first_arg_type = isset($call_args[0]) ? $statements_source->node_data->getType($call_args[0]->value) : null;
        $third_arg_type = isset($call_args[2]) ? $statements_source->node_data->getType($call_args[2]->value) : null;

        if ($third_arg_type) {
            if ($first_arg_type
                && $first_arg_type->isSingleIntLiteral()
                && $first_arg_type->getSingleIntLiteral()->value === 0
            ) {
                return new Type\Union([
                    new Type\Atomic\TNonEmptyList(
                        clone $third_arg_type
                    )
                ]);
            }

            return new Type\Union([
                new Type\Atomic\TArray([
                    Type::getInt(),
                    clone $third_arg_type
                ])
            ]);
        }

        return new Type\Union([
            new Type\Atomic\TArray([
                Type::getInt(),
                Type::getMixed()
            ])
        ]);
    }
}
