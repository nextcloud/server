<?php
namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\Hook\FunctionParamsProviderInterface;
use Psalm\StatementsSource;
use function strtolower;

class FunctionParamsProvider
{
    /**
     * @var array<
     *   string,
     *   array<\Closure(
     *     StatementsSource,
     *     string,
     *     array<PhpParser\Node\Arg>,
     *     ?Context=,
     *     ?CodeLocation=
     *   ) : ?array<int, \Psalm\Storage\FunctionLikeParameter>>
     * >
     */
    private static $handlers = [];

    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @param  class-string<FunctionParamsProviderInterface> $class
     *
     */
    public function registerClass(string $class): void
    {
        $callable = \Closure::fromCallable([$class, 'getFunctionParams']);

        foreach ($class::getFunctionIds() as $function_id) {
            $this->registerClosure($function_id, $callable);
        }
    }

    /**
     * @param  \Closure(
     *     StatementsSource,
     *     string,
     *     array<PhpParser\Node\Arg>,
     *     ?Context=,
     *     ?CodeLocation=
     *   ) : ?array<int, \Psalm\Storage\FunctionLikeParameter> $c
     *
     */
    public function registerClosure(string $fq_classlike_name, \Closure $c): void
    {
        self::$handlers[strtolower($fq_classlike_name)][] = $c;
    }

    public function has(string $fq_classlike_name) : bool
    {
        return isset(self::$handlers[strtolower($fq_classlike_name)]);
    }

    /**
     * @param array<PhpParser\Node\Arg>  $call_args
     *
     * @return  ?array<int, \Psalm\Storage\FunctionLikeParameter>
     */
    public function getFunctionParams(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        ?Context $context = null,
        ?CodeLocation $code_location = null
    ): ?array {
        foreach (self::$handlers[strtolower($function_id)] as $class_handler) {
            $result = $class_handler(
                $statements_source,
                $function_id,
                $call_args,
                $context,
                $code_location
            );

            if ($result) {
                return $result;
            }
        }

        return null;
    }
}
