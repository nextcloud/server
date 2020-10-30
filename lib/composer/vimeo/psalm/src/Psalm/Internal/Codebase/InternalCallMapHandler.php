<?php
namespace Psalm\Internal\Codebase;

use function array_shift;
use function assert;
use function count;
use function file_exists;
use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use function dirname;
use function strtolower;
use function substr;
use function version_compare;

/**
 * @internal
 *
 * Gets values from the call map array, which stores data about native functions and methods
 */
class InternalCallMapHandler
{
    private const PHP_MAJOR_VERSION = 8;
    private const PHP_MINOR_VERSION = 0;
    private const LOWEST_AVAILABLE_DELTA = 71;

    /**
     * @var ?int
     */
    private static $loaded_php_major_version = null;
    /**
     * @var ?int
     */
    private static $loaded_php_minor_version = null;

    /**
     * @var array<array<int|string,string>>|null
     */
    private static $call_map = null;

    /**
     * @var array<array<int, TCallable>>|null
     */
    private static $call_map_callables = [];

    /**
     * @var array<string, list<list<Type\TaintKind::*>>>
     */
    private static $taint_sink_map = [];

    /**
     * @param  array<int, PhpParser\Node\Arg>   $args
     */
    public static function getCallableFromCallMapById(
        Codebase $codebase,
        string $method_id,
        array $args,
        ?\Psalm\Internal\Provider\NodeDataProvider $nodes
    ): TCallable {
        $possible_callables = self::getCallablesFromCallMap($method_id);

        if ($possible_callables === null) {
            throw new \UnexpectedValueException(
                'Not expecting $function_param_options to be null for ' . $method_id
            );
        }

        return self::getMatchingCallableFromCallMapOptions(
            $codebase,
            $possible_callables,
            $args,
            $nodes,
            $method_id
        );
    }

    /**
     * @param  array<int, TCallable>  $callables
     * @param  array<int, PhpParser\Node\Arg>                 $args
     *
     */
    public static function getMatchingCallableFromCallMapOptions(
        Codebase $codebase,
        array $callables,
        array $args,
        ?\Psalm\NodeTypeProvider $nodes,
        string $method_id
    ): TCallable {
        if (count($callables) === 1) {
            return $callables[0];
        }

        $matching_param_count_callable = null;
        $matching_coerced_param_count_callable = null;

        foreach ($callables as $possible_callable) {
            $possible_function_params = $possible_callable->params;

            assert($possible_function_params !== null);

            $all_args_match = true;
            $type_coerced = false;

            $last_param = count($possible_function_params)
                ? $possible_function_params[count($possible_function_params) - 1]
                : null;

            $mandatory_param_count = count($possible_function_params);

            foreach ($possible_function_params as $i => $possible_function_param) {
                if ($possible_function_param->is_optional) {
                    $mandatory_param_count = $i;
                    break;
                }
            }

            if ($mandatory_param_count > count($args) && !($last_param && $last_param->is_variadic)) {
                continue;
            }

            foreach ($args as $argument_offset => $arg) {
                if ($argument_offset >= count($possible_function_params)) {
                    if (!$last_param || !$last_param->is_variadic) {
                        $all_args_match = false;
                        break;
                    }

                    $function_param = $last_param;
                } else {
                    $function_param = $possible_function_params[$argument_offset];
                }

                $param_type = $function_param->type;

                if (!$param_type) {
                    continue;
                }

                if (!$nodes
                    || !($arg_type = $nodes->getType($arg->value))
                ) {
                    continue;
                }

                if ($arg_type->hasMixed()) {
                    continue;
                }

                if ($arg->unpack && !$function_param->is_variadic) {
                    if ($arg_type->hasArray()) {
                        /**
                         * @psalm-suppress PossiblyUndefinedStringArrayOffset
                         * @var Type\Atomic\TArray|Type\Atomic\TKeyedArray|Type\Atomic\TList
                         */
                        $array_atomic_type = $arg_type->getAtomicTypes()['array'];

                        if ($array_atomic_type instanceof Type\Atomic\TKeyedArray) {
                            $arg_type = $array_atomic_type->getGenericValueType();
                        } elseif ($array_atomic_type instanceof Type\Atomic\TList) {
                            $arg_type = $array_atomic_type->type_param;
                        } else {
                            $arg_type = $array_atomic_type->type_params[1];
                        }
                    }
                }

                $arg_result = new \Psalm\Internal\Type\Comparator\TypeComparisonResult();

                if (UnionTypeComparator::isContainedBy(
                    $codebase,
                    $arg_type,
                    $param_type,
                    true,
                    true,
                    $arg_result
                ) || $arg_result->type_coerced) {
                    if ($arg_result->type_coerced) {
                        $type_coerced = true;
                    }

                    continue;
                }

                $all_args_match = false;
                break;
            }

            if (count($args) === count($possible_function_params)) {
                $matching_param_count_callable = $possible_callable;
            }

            if ($all_args_match && (!$type_coerced || $method_id === 'max' || $method_id === 'min')) {
                return $possible_callable;
            }

            if ($all_args_match) {
                $matching_coerced_param_count_callable = $possible_callable;
            }
        }

        if ($matching_coerced_param_count_callable) {
            return $matching_coerced_param_count_callable;
        }

        if ($matching_param_count_callable) {
            return $matching_param_count_callable;
        }

        // if we don't succeed in finding a match, set to the first possible and wait for issues below
        return $callables[0];
    }

    /**
     * @return array<int, TCallable>|null
     */
    public static function getCallablesFromCallMap(string $function_id): ?array
    {
        $call_map_key = strtolower($function_id);

        if (isset(self::$call_map_callables[$call_map_key])) {
            return self::$call_map_callables[$call_map_key];
        }

        $call_map = self::getCallMap();

        if (!isset($call_map[$call_map_key])) {
            return null;
        }

        $call_map_functions = [];
        $call_map_functions[] = $call_map[$call_map_key];

        for ($i = 1; $i < 10; ++$i) {
            if (!isset($call_map[$call_map_key . '\'' . $i])) {
                break;
            }

            $call_map_functions[] = $call_map[$call_map_key . '\'' . $i];
        }

        $possible_callables = [];

        foreach ($call_map_functions as $call_map_function_args) {
            $return_type_string = array_shift($call_map_function_args);

            if (!$return_type_string) {
                $return_type = Type::getMixed();
            } else {
                $return_type = Type::parseString($return_type_string);
            }

            $function_params = [];

            $arg_offset = 0;

            /** @var string $arg_name - key type changed with above array_shift */
            foreach ($call_map_function_args as $arg_name => $arg_type) {
                $by_reference = false;
                $optional = false;
                $variadic = false;

                if ($arg_name[0] === '&') {
                    $arg_name = substr($arg_name, 1);
                    $by_reference = true;
                }

                if (substr($arg_name, -1) === '=') {
                    $arg_name = substr($arg_name, 0, -1);
                    $optional = true;
                }

                if (substr($arg_name, 0, 3) === '...') {
                    $arg_name = substr($arg_name, 3);
                    $variadic = true;
                }

                $param_type = $arg_type
                    ? Type::parseString($arg_type)
                    : Type::getMixed();

                $out_type = null;

                if (\strlen($arg_name) > 2 && $arg_name[0] === 'w' && $arg_name[1] === '_') {
                    $out_type = $param_type;
                    $param_type = Type::getMixed();
                }

                $function_param = new FunctionLikeParameter(
                    $arg_name,
                    $by_reference,
                    $param_type,
                    null,
                    null,
                    $optional,
                    false,
                    $variadic
                );

                if ($out_type) {
                    $function_param->out_type = $out_type;
                }

                if ($arg_name === 'haystack') {
                    $function_param->expect_variable = true;
                }

                if (isset(self::$taint_sink_map[$call_map_key][$arg_offset])) {
                    $function_param->sinks = self::$taint_sink_map[$call_map_key][$arg_offset];
                }

                $function_param->signature_type = null;

                $function_params[] = $function_param;

                $arg_offset++;
            }

            $possible_callables[] = new TCallable('callable', $function_params, $return_type);
        }

        self::$call_map_callables[$call_map_key] = $possible_callables;

        return $possible_callables;
    }

    /**
     * Gets the method/function call map
     *
     * @return array<string, array<int|string, string>>
     * @psalm-suppress MixedInferredReturnType as the use of require buggers things up
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public static function getCallMap(): array
    {
        $codebase = ProjectAnalyzer::getInstance()->getCodebase();
        $analyzer_major_version = $codebase->php_major_version;
        $analyzer_minor_version = $codebase->php_minor_version;

        $analyzer_version = $analyzer_major_version . '.' . $analyzer_minor_version;
        $current_version = self::PHP_MAJOR_VERSION . '.' . self::PHP_MINOR_VERSION;

        $analyzer_version_int = (int) ($analyzer_major_version . $analyzer_minor_version);
        $current_version_int = (int) (self::PHP_MAJOR_VERSION . self::PHP_MINOR_VERSION);

        if (self::$call_map !== null
            && $analyzer_major_version === self::$loaded_php_major_version
            && $analyzer_minor_version === self::$loaded_php_minor_version
        ) {
            return self::$call_map;
        }

        /** @var array<string, array<int|string, string>> */
        $call_map = require(dirname(__DIR__, 4) . '/dictionaries/CallMap.php');

        self::$call_map = [];

        foreach ($call_map as $key => $value) {
            $cased_key = strtolower($key);
            self::$call_map[$cased_key] = $value;
        }

        /**
         * @var array<string, list<list<Type\TaintKind::*>>>
         */
        $taint_map = require(dirname(__DIR__, 4) . '/dictionaries/InternalTaintSinkMap.php');

        foreach ($taint_map as $key => $value) {
            $cased_key = strtolower($key);
            self::$taint_sink_map[$cased_key] = $value;
        }

        if (version_compare($analyzer_version, $current_version, '<')) {
            // the following assumes both minor and major versions a single digits
            for ($i = $current_version_int; $i > $analyzer_version_int && $i >= self::LOWEST_AVAILABLE_DELTA; --$i) {
                $delta_file = dirname(__DIR__, 4) . '/dictionaries/CallMap_' . $i . '_delta.php';
                if (!file_exists($delta_file)) {
                    continue;
                }
                /**
                 * @var array{
                 *     old: array<string, array<int|string, string>>,
                 *     new: array<string, array<int|string, string>>
                 * }
                 * @psalm-suppress UnresolvableInclude
                 */
                $diff_call_map = require($delta_file);

                foreach ($diff_call_map['new'] as $key => $_) {
                    $cased_key = strtolower($key);
                    unset(self::$call_map[$cased_key]);
                }

                foreach ($diff_call_map['old'] as $key => $value) {
                    $cased_key = strtolower($key);
                    self::$call_map[$cased_key] = $value;
                }
            }
        }

        self::$loaded_php_major_version = $analyzer_major_version;
        self::$loaded_php_minor_version = $analyzer_minor_version;

        return self::$call_map;
    }

    public static function inCallMap(string $key): bool
    {
        return isset(self::getCallMap()[strtolower($key)]);
    }

    public static function clearCache() : void
    {
        self::$call_map_callables = [];
    }
}
