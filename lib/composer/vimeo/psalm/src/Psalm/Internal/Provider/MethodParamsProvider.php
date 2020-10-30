<?php
namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\Hook\MethodParamsProviderInterface;
use Psalm\StatementsSource;
use function strtolower;

class MethodParamsProvider
{
    /**
     * @var array<
     *   string,
     *   array<\Closure(
     *     string,
     *     string,
     *     ?array<PhpParser\Node\Arg>=,
     *     ?StatementsSource=,
     *     ?Context=,
     *     ?CodeLocation=
     *   ) : ?array<int, \Psalm\Storage\FunctionLikeParameter>>
     * >
     */
    private static $handlers = [];

    public function __construct()
    {
        self::$handlers = [];

        $this->registerClass(ReturnTypeProvider\PdoStatementSetFetchMode::class);
    }

    /**
     * @param  class-string<MethodParamsProviderInterface> $class
     *
     */
    public function registerClass(string $class): void
    {
        $callable = \Closure::fromCallable([$class, 'getMethodParams']);

        foreach ($class::getClassLikeNames() as $fq_classlike_name) {
            $this->registerClosure($fq_classlike_name, $callable);
        }
    }

    /**
     * @param  \Closure(
     *     string,
     *     string,
     *     ?array<PhpParser\Node\Arg>=,
     *     ?StatementsSource=,
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
     * @param ?array<PhpParser\Node\Arg>  $call_args
     *
     * @return  ?array<int, \Psalm\Storage\FunctionLikeParameter>
     */
    public function getMethodParams(
        string $fq_classlike_name,
        string $method_name_lowercase,
        ?array $call_args = null,
        ?StatementsSource $statements_source = null,
        ?Context $context = null,
        ?CodeLocation $code_location = null
    ): ?array {
        foreach (self::$handlers[strtolower($fq_classlike_name)] as $class_handler) {
            $result = $class_handler(
                $fq_classlike_name,
                $method_name_lowercase,
                $call_args,
                $statements_source,
                $context,
                $code_location
            );

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }
}
