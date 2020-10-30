<?php
namespace Psalm\Type\Atomic;

use function array_keys;
use function array_map;
use function count;
use function implode;
use Psalm\Codebase;
use Psalm\Type\Atomic;
use Psalm\Type\Union;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\UnionTemplateHandler;
use function array_merge;
use function array_values;

class TObjectWithProperties extends TObject
{
    use HasIntersectionTrait;

    /**
     * @var array<string|int, Union>
     */
    public $properties;

    /**
     * @var array<string, string>
     */
    public $methods;

    /**
     * Constructs a new instance of a generic type
     *
     * @param array<string|int, Union> $properties
     * @param array<string, string> $methods
     */
    public function __construct(array $properties, array $methods = [])
    {
        $this->properties = $properties;
        $this->methods = $methods;
    }

    public function __toString(): string
    {
        $extra_types = '';

        if ($this->extra_types) {
            $extra_types = '&' . implode('&', $this->extra_types);
        }

        $properties_string = implode(
            ', ',
            array_map(
                /**
                 * @param  string|int $name
                 */
                function ($name, Union $type): string {
                    return $name . ($type->possibly_undefined ? '?' : '') . ':' . $type;
                },
                array_keys($this->properties),
                $this->properties
            )
        );

        $methods_string = implode(
            ', ',
            array_map(
                function (string $name): string {
                    return $name . '()';
                },
                array_keys($this->methods)
            )
        );

        return 'object{'
            . $properties_string . ($methods_string && $properties_string ? ', ' : '')
            . $methods_string
            . '}' . $extra_types;
    }

    public function getId(bool $nested = false): string
    {
        $extra_types = '';

        if ($this->extra_types) {
            $extra_types = '&' . implode('&', $this->extra_types);
        }

        $properties_string = implode(
            ', ',
            array_map(
                /**
                 * @param  string|int $name
                 */
                function ($name, Union $type): string {
                    return $name . ($type->possibly_undefined ? '?' : '') . ':' . $type->getId();
                },
                array_keys($this->properties),
                $this->properties
            )
        );

        $methods_string = implode(
            ', ',
            array_map(
                function (string $name): string {
                    return $name . '()';
                },
                array_keys($this->methods)
            )
        );

        return 'object{'
            . $properties_string . ($methods_string && $properties_string ? ', ' : '')
            . $methods_string
            . '}' . $extra_types;
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
            return 'object';
        }

        return 'object{' .
                implode(
                    ', ',
                    array_map(
                        /**
                         * @param  string|int $name
                         */
                        function (
                            $name,
                            Union $type
                        ) use (
                            $namespace,
                            $aliased_classes,
                            $this_class,
                            $use_phpdoc_format
                        ): string {
                            return $name . ($type->possibly_undefined ? '?' : '') . ':' . $type->toNamespacedString(
                                $namespace,
                                $aliased_classes,
                                $this_class,
                                $use_phpdoc_format
                            );
                        },
                        array_keys($this->properties),
                        $this->properties
                    )
                ) .
                '}';
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
        return $this->getKey();
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }

    public function __clone()
    {
        foreach ($this->properties as &$property) {
            $property = clone $property;
        }
    }

    public function equals(Atomic $other_type): bool
    {
        if (!$other_type instanceof self) {
            return false;
        }

        if (count($this->properties) !== count($other_type->properties)) {
            return false;
        }

        if ($this->methods !== $other_type->methods) {
            return false;
        }

        foreach ($this->properties as $property_name => $property_type) {
            if (!isset($other_type->properties[$property_name])) {
                return false;
            }

            if (!$property_type->equals($other_type->properties[$property_name])) {
                return false;
            }
        }

        return true;
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
        $object_like = clone $this;

        foreach ($this->properties as $offset => $property) {
            $input_type_param = null;

            if ($input_type instanceof Atomic\TKeyedArray
                && isset($input_type->properties[$offset])
            ) {
                $input_type_param = $input_type->properties[$offset];
            }

            $object_like->properties[$offset] = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                $property,
                $template_result,
                $codebase,
                $statements_analyzer,
                $input_type_param,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_upper_bound,
                $depth
            );
        }

        return $object_like;
    }

    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ) : void {
        foreach ($this->properties as $property) {
            $property->replaceTemplateTypesWithArgTypes(
                $template_result,
                $codebase
            );
        }
    }

    public function getChildNodes() : array
    {
        return array_merge($this->properties, $this->extra_types !== null ? array_values($this->extra_types) : []);
    }

    public function getAssertionString(): string
    {
        return $this->getKey();
    }
}
