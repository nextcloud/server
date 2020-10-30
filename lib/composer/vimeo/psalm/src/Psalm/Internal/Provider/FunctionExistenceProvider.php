<?php
namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\Plugin\Hook\FunctionExistenceProviderInterface;
use Psalm\StatementsSource;
use function strtolower;

class FunctionExistenceProvider
{
    /**
     * @var array<
     *   string,
     *   array<\Closure(
     *     StatementsSource,
     *     string
     *   ) : ?bool>
     * >
     */
    private static $handlers = [];

    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @param  class-string<FunctionExistenceProviderInterface> $class
     *
     */
    public function registerClass(string $class): void
    {
        $callable = \Closure::fromCallable([$class, 'doesFunctionExist']);

        foreach ($class::getFunctionIds() as $function_id) {
            $this->registerClosure($function_id, $callable);
        }
    }

    /**
     * /**
     * @param \Closure(
     *     StatementsSource,
     *     string
     *   ) : ?bool $c
     *
     */
    public function registerClosure(string $function_id, \Closure $c): void
    {
        self::$handlers[$function_id][] = $c;
    }

    public function has(string $function_id) : bool
    {
        return isset(self::$handlers[strtolower($function_id)]);
    }

    /**
     * @param  array<PhpParser\Node\Arg>  $call_args
     *
     */
    public function doesFunctionExist(
        StatementsSource $statements_source,
        string $function_id
    ): ?bool {
        foreach (self::$handlers[strtolower($function_id)] as $function_handler) {
            $function_exists = $function_handler(
                $statements_source,
                $function_id
            );

            if ($function_exists !== null) {
                return $function_exists;
            }
        }

        return null;
    }
}
