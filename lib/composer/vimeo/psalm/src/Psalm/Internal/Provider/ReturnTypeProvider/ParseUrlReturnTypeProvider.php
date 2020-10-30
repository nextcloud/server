<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use function count;
use const PHP_URL_FRAGMENT;
use const PHP_URL_HOST;
use const PHP_URL_PASS;
use const PHP_URL_PATH;
use const PHP_URL_PORT;
use const PHP_URL_QUERY;
use const PHP_URL_SCHEME;
use const PHP_URL_USER;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\StatementsSource;
use Psalm\Type;

class ParseUrlReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['parse_url'];
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

        if (count($call_args) > 1) {
            if ($component_type = $statements_source->node_data->getType($call_args[1]->value)) {
                if (!$component_type->hasMixed()) {
                    $codebase = $statements_source->getCodebase();

                    $acceptable_string_component_type = new Type\Union([
                        new Type\Atomic\TLiteralInt(PHP_URL_SCHEME),
                        new Type\Atomic\TLiteralInt(PHP_URL_USER),
                        new Type\Atomic\TLiteralInt(PHP_URL_PASS),
                        new Type\Atomic\TLiteralInt(PHP_URL_HOST),
                        new Type\Atomic\TLiteralInt(PHP_URL_PATH),
                        new Type\Atomic\TLiteralInt(PHP_URL_QUERY),
                        new Type\Atomic\TLiteralInt(PHP_URL_FRAGMENT),
                    ]);

                    $acceptable_int_component_type = new Type\Union([
                        new Type\Atomic\TLiteralInt(PHP_URL_PORT),
                    ]);

                    if (UnionTypeComparator::isContainedBy(
                        $codebase,
                        $component_type,
                        $acceptable_string_component_type
                    )) {
                        $nullable_falsable_string = new Type\Union([
                            new Type\Atomic\TString,
                            new Type\Atomic\TFalse,
                            new Type\Atomic\TNull,
                        ]);

                        $codebase = $statements_source->getCodebase();

                        if ($codebase->config->ignore_internal_nullable_issues) {
                            $nullable_falsable_string->ignore_nullable_issues = true;
                        }

                        if ($codebase->config->ignore_internal_falsable_issues) {
                            $nullable_falsable_string->ignore_falsable_issues = true;
                        }

                        return $nullable_falsable_string;
                    }

                    if (UnionTypeComparator::isContainedBy(
                        $codebase,
                        $component_type,
                        $acceptable_int_component_type
                    )) {
                        $nullable_falsable_int = new Type\Union([
                            new Type\Atomic\TInt,
                            new Type\Atomic\TFalse,
                            new Type\Atomic\TNull,
                        ]);

                        $codebase = $statements_source->getCodebase();

                        if ($codebase->config->ignore_internal_nullable_issues) {
                            $nullable_falsable_int->ignore_nullable_issues = true;
                        }

                        if ($codebase->config->ignore_internal_falsable_issues) {
                            $nullable_falsable_int->ignore_falsable_issues = true;
                        }

                        return $nullable_falsable_int;
                    }
                }
            }

            $nullable_string_or_int = new Type\Union([
                new Type\Atomic\TString,
                new Type\Atomic\TInt,
                new Type\Atomic\TNull,
            ]);

            $codebase = $statements_source->getCodebase();

            if ($codebase->config->ignore_internal_nullable_issues) {
                $nullable_string_or_int->ignore_nullable_issues = true;
            }

            return $nullable_string_or_int;
        }

        $component_types = [
            'scheme' => Type::getString(),
            'user' => Type::getString(),
            'pass' => Type::getString(),
            'host' => Type::getString(),
            'port' => Type::getInt(),
            'path' => Type::getString(),
            'query' => Type::getString(),
            'fragment' => Type::getString(),
        ];

        foreach ($component_types as $component_type) {
            $component_type->possibly_undefined = true;
        }

        $return_type = new Type\Union([
            new Type\Atomic\TKeyedArray($component_types),
            new Type\Atomic\TFalse(),
        ]);

        if ($statements_source->getCodebase()->config->ignore_internal_falsable_issues) {
            $return_type->ignore_falsable_issues = true;
        }

        return $return_type;
    }
}
