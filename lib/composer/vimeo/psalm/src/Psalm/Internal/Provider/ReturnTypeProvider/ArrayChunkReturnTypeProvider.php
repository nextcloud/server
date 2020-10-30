<?php declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use function count;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Type\ArrayType;
use Psalm\StatementsSource;
use Psalm\Type;

class ArrayChunkReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds(): array
    {
        return ['array_chunk'];
    }

    public static function getFunctionReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ): ?Type\Union {
        if (count($call_args) >= 2
            && ($array_arg_type = $statements_source->getNodeTypeProvider()->getType($call_args[0]->value))
            && $array_arg_type->isSingle()
            && $array_arg_type->hasArray()
            && ($array_type = ArrayType::infer($array_arg_type->getAtomicTypes()['array']))
        ) {
            $preserve_keys = isset($call_args[2])
                && ($preserve_keys_arg_type = $statements_source->getNodeTypeProvider()->getType($call_args[2]->value))
                && (string) $preserve_keys_arg_type !== 'false';

            return new Type\Union([
                new Type\Atomic\TList(
                    new Type\Union([
                        $preserve_keys
                            ? new Type\Atomic\TNonEmptyArray([$array_type->key, $array_type->value])
                            : new Type\Atomic\TNonEmptyList($array_type->value)
                    ])
                )
            ]);
        }

        return new Type\Union([new Type\Atomic\TList(Type::getArray())]);
    }
}
