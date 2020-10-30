<?php
namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\Hook\MethodVisibilityProviderInterface;
use Psalm\StatementsSource;
use function strtolower;

class MethodVisibilityProvider
{
    /**
     * @var array<
     *   string,
     *   array<\Closure(
     *     StatementsSource,
     *     string,
     *     string,
     *     Context,
     *     ?CodeLocation
     *   ) : ?bool>
     * >
     */
    private static $handlers = [];

    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @param  class-string<MethodVisibilityProviderInterface> $class
     *
     */
    public function registerClass(string $class): void
    {
        $callable = \Closure::fromCallable([$class, 'isMethodVisible']);

        foreach ($class::getClassLikeNames() as $fq_classlike_name) {
            $this->registerClosure($fq_classlike_name, $callable);
        }
    }

    /**
     * /**
     * @param \Closure(
     *     StatementsSource,
     *     string,
     *     string,
     *     Context,
     *     ?CodeLocation
     *   ) : ?bool $c
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
     * @param  array<PhpParser\Node\Arg>  $call_args
     *
     */
    public function isMethodVisible(
        StatementsSource $source,
        string $fq_classlike_name,
        string $method_name,
        Context $context,
        ?CodeLocation $code_location = null
    ): ?bool {
        foreach (self::$handlers[strtolower($fq_classlike_name)] as $method_handler) {
            $method_visible = $method_handler(
                $source,
                $fq_classlike_name,
                $method_name,
                $context,
                $code_location
            );

            if ($method_visible !== null) {
                return $method_visible;
            }
        }

        return null;
    }
}
