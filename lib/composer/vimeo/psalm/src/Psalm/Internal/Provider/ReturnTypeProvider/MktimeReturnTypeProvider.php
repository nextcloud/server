<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class MktimeReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return [
            'mktime',
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

        foreach ($call_args as $call_arg) {
            if (!($call_arg_type = $statements_source->node_data->getType($call_arg->value))
                || !$call_arg_type->isInt()
            ) {
                $value_type = new Type\Union([new Type\Atomic\TInt, new Type\Atomic\TFalse]);

                $codebase = $statements_source->getCodebase();

                if ($codebase->config->ignore_internal_falsable_issues) {
                    $value_type->ignore_falsable_issues = true;
                }

                return $value_type;
            }
        }

        return Type::getInt();
    }
}
