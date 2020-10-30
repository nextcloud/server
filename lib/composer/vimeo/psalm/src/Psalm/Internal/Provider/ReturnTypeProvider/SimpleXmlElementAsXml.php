<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use function count;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class SimpleXmlElementAsXml implements \Psalm\Plugin\Hook\MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames() : array
    {
        return ['SimpleXMLElement'];
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
        if ($method_name_lowercase === 'asxml'
            && !count($call_args)
        ) {
            return Type::parseString('string|false');
        }

        return null;
    }
}
