<?php
namespace Psalm\Type\Atomic;

use function get_class;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\UnionTemplateHandler;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * Represents an array where the type of each value
 * is a function of its string key value
 */
class TClassStringMap extends \Psalm\Type\Atomic
{
    /**
     * @var string
     */
    public $param_name;

    /**
     * @var ?TNamedObject
     */
    public $as_type;

    /**
     * @var Union
     */
    public $value_param;

    public const KEY = 'class-string-map';

    /**
     * Constructs a new instance of a list
     */
    public function __construct(string $param_name, ?TNamedObject $as_type, Union $value_param)
    {
        $this->value_param = $value_param;
        $this->param_name = $param_name;
        $this->as_type = $as_type;
    }

    public function __toString(): string
    {
        /** @psalm-suppress MixedOperand */
        return static::KEY
            . '<'
            . $this->param_name
            . ' as '
            . ($this->as_type ? (string) $this->as_type : 'object')
            . ', '
            . ((string) $this->value_param)
            . '>';
    }

    public function getId(bool $nested = false): string
    {
        /** @psalm-suppress MixedOperand */
        return static::KEY
            . '<'
            . $this->param_name
            . ' as '
            . ($this->as_type ? (string) $this->as_type : 'object')
            . ', '
            . $this->value_param->getId()
            . '>';
    }

    public function __clone()
    {
        $this->value_param = clone $this->value_param;
    }

    /**
     * @param  array<string, string> $aliased_classes
     *
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        if ($use_phpdoc_format) {
            return (new TArray([Type::getString(), $this->value_param]))
                ->toNamespacedString(
                    $namespace,
                    $aliased_classes,
                    $this_class,
                    $use_phpdoc_format
                );
        }

        /** @psalm-suppress MixedOperand */
        return static::KEY
            . '<'
            . $this->param_name
            . ($this->as_type ? ' as ' . $this->as_type : '')
            . ', '
            . $this->value_param->toNamespacedString(
                $namespace,
                $aliased_classes,
                $this_class,
                $use_phpdoc_format
            )
            . '>';
    }

    /**
     * @param  array<string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): string {
        return 'array';
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'array';
    }

    public function replaceTemplateTypesWithStandins(
        TemplateResult $template_result,
        ?Codebase $codebase = null,
        ?StatementsAnalyzer $statements_analyzer = null,
        ?Atomic $input_type = null,
        ?int $input_arg_offset = null,
        ?string $calling_class = null,
        ?string $calling_function = null,
        bool $replace = true,
        bool $add_upper_bound = false,
        int $depth = 0
    ) : Atomic {
        $map = clone $this;

        foreach ([Type::getString(), $map->value_param] as $offset => $type_param) {
            $input_type_param = null;

            if (($input_type instanceof Atomic\TGenericObject
                    || $input_type instanceof Atomic\TIterable
                    || $input_type instanceof Atomic\TArray)
                &&
                    isset($input_type->type_params[$offset])
            ) {
                $input_type_param = clone $input_type->type_params[$offset];
            } elseif ($input_type instanceof Atomic\TKeyedArray) {
                if ($offset === 0) {
                    $input_type_param = $input_type->getGenericKeyType();
                } else {
                    $input_type_param = $input_type->getGenericValueType();
                }
            } elseif ($input_type instanceof Atomic\TList) {
                if ($offset === 0) {
                    continue;
                }

                $input_type_param = clone $input_type->type_param;
            }

            $value_param = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                $type_param,
                $template_result,
                $codebase,
                $statements_analyzer,
                $input_type_param,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_upper_bound,
                $depth + 1
            );

            if ($offset === 1) {
                $map->value_param = $value_param;
            }
        }

        return $map;
    }

    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ) : void {
        $this->value_param->replaceTemplateTypesWithArgTypes($template_result, $codebase);
    }

    public function getChildNodes() : array
    {
        return [$this->value_param];
    }

    public function equals(Atomic $other_type): bool
    {
        if (get_class($other_type) !== static::class) {
            return false;
        }

        if (!$this->value_param->equals($other_type->value_param)) {
            return false;
        }

        return true;
    }

    public function getAssertionString(): string
    {
        return $this->getKey();
    }

    public function getStandinKeyParam() : Type\Union
    {
        return new Type\Union([
            new TTemplateParamClass(
                $this->param_name,
                $this->as_type ? $this->as_type->value : 'object',
                $this->as_type,
                'class-string-map'
            )
        ]);
    }
}
