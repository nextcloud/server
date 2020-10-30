<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class DomNodeAppendChild implements \Psalm\Plugin\Hook\MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames() : array
    {
        return ['DomNode'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     */
    public static function getMethodReturnType(
        StatementsSource $source,
        string $fq_classlike_name,
        string $method_name_lowercase,
        array $call_args,
        Context $context,
        CodeLocation $code_location,
        ?array $template_type_parameters = null,
        ?string $called_fq_classlike_name = null,
        ?string $called_method_name_lowercase = null
    ): ?Type\Union {
        if (!$source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
            return Type::getMixed();
        }

        if ($method_name_lowercase === 'appendchild'
            && ($first_arg_type = $source->node_data->getType($call_args[0]->value))
            && $first_arg_type->hasObjectType()
        ) {
            return clone $first_arg_type;
        }

        return null;
    }
}
