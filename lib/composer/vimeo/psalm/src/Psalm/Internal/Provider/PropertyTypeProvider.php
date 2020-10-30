<?php
namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\Context;
use Psalm\Plugin\Hook\PropertyTypeProviderInterface;
use Psalm\StatementsSource;
use Psalm\Type;
use function strtolower;

class PropertyTypeProvider
{
    /**
     * @var array<
     *   string,
     *   array<\Closure(
     *     string,
     *     string,
     *     bool,
     *     ?StatementsSource=,
     *     ?Context=
     *   ) : ?Type\Union>
     * >
     */
    private static $handlers = [];

    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @param  class-string<PropertyTypeProviderInterface> $class
     *
     */
    public function registerClass(string $class): void
    {
        $callable = \Closure::fromCallable([$class, 'getPropertyType']);

        foreach ($class::getClassLikeNames() as $fq_classlike_name) {
            $this->registerClosure($fq_classlike_name, $callable);
        }
    }

    /**
     * /**
     * @param \Closure(
     *     string,
     *     string,
     *     bool,
     *     ?StatementsSource=,
     *     ?Context=
     *   ) : ?Type\Union $c
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
    public function getPropertyType(
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        ?StatementsSource $source = null,
        ?Context $context = null
    ): ?Type\Union {
        foreach (self::$handlers[strtolower($fq_classlike_name)] as $property_handler) {
            $property_type = $property_handler(
                $fq_classlike_name,
                $property_name,
                $read_mode,
                $source,
                $context
            );

            if ($property_type !== null) {
                return $property_type;
            }
        }

        return null;
    }
}
