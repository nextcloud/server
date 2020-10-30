<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use function assert;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\StatementsSource;
use Psalm\Type;

class IteratorToArrayReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return [
            'iterator_to_array',
        ];
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

        if (($first_arg_type = $statements_source->node_data->getType($call_args[0]->value))
            && $first_arg_type->hasObjectType()
        ) {
            $key_type = null;
            $value_type = null;

            $codebase = $statements_source->getCodebase();

            foreach ($first_arg_type->getAtomicTypes() as $call_arg_atomic_type) {
                if ($call_arg_atomic_type instanceof Type\Atomic\TNamedObject
                    && AtomicTypeComparator::isContainedBy(
                        $codebase,
                        $call_arg_atomic_type,
                        new Type\Atomic\TIterable([Type::getMixed(), Type::getMixed()])
                    )
                ) {
                    $has_valid_iterator = true;
                    ForeachAnalyzer::handleIterable(
                        $statements_source,
                        $call_arg_atomic_type,
                        $call_args[0]->value,
                        $codebase,
                        $context,
                        $key_type,
                        $value_type,
                        $has_valid_iterator
                    );
                }
            }

            if ($value_type) {
                $second_arg_type = isset($call_args[1])
                    ? $statements_source->node_data->getType($call_args[1]->value)
                    : null;

                if ($second_arg_type
                    && ((string) $second_arg_type === 'false')
                ) {
                    return new Type\Union([
                        new Type\Atomic\TList($value_type),
                    ]);
                }

                $key_type = $key_type
                    && (!isset($call_args[1])
                        || ($second_arg_type && ((string) $second_arg_type === 'true')))
                    ? $key_type
                    : Type::getArrayKey();

                if ($key_type->hasMixed()) {
                    $key_type = Type::getArrayKey();
                }

                return new Type\Union([
                    new Type\Atomic\TArray([
                        $key_type,
                        $value_type,
                    ]),
                ]);
            }
        }

        $callmap_callables = InternalCallMapHandler::getCallablesFromCallMap($function_id);

        assert($callmap_callables && $callmap_callables[0]->return_type);

        return $callmap_callables[0]->return_type;
    }
}
