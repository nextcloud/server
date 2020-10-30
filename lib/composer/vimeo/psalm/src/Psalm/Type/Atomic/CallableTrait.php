<?php
namespace Psalm\Type\Atomic;

use function array_map;
use function count;
use function implode;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\UnionTemplateHandler;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

trait CallableTrait
{
    /**
     * @var array<int, FunctionLikeParameter>|null
     */
    public $params = [];

    /**
     * @var Union|null
     */
    public $return_type;

    /**
     * @var ?bool
     */
    public $is_pure;

    /**
     * Constructs a new instance of a generic type
     *
     * @param array<int, FunctionLikeParameter> $params
     */
    public function __construct(
        string $value = 'callable',
        ?array $params = null,
        ?Union $return_type = null,
        ?bool $is_pure = null
    ) {
        $this->value = $value;
        $this->params = $params;
        $this->return_type = $return_type;
        $this->is_pure = $is_pure;
    }

    public function __clone()
    {
        if ($this->params) {
            foreach ($this->params as &$param) {
                $param = clone $param;
            }
        }

        $this->return_type = $this->return_type ? clone $this->return_type : null;
    }

    public function getKey(bool $include_extra = true): string
    {
        return $this->__toString();
    }

    /**
     * @param  array<string, string> $aliased_classes
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        if ($use_phpdoc_format) {
            if ($this instanceof TNamedObject) {
                return parent::toNamespacedString($namespace, $aliased_classes, $this_class, true);
            }

            return $this->value;
        }

        $param_string = '';
        $return_type_string = '';

        if ($this->params !== null) {
            $param_string = '(' . implode(
                ', ',
                array_map(
                    /**
                     * @return string
                     */
                    function (FunctionLikeParameter $param) use ($namespace, $aliased_classes, $this_class): string {
                        if (!$param->type) {
                            $type_string = 'mixed';
                        } else {
                            $type_string = $param->type->toNamespacedString(
                                $namespace,
                                $aliased_classes,
                                $this_class,
                                false
                            );
                        }

                        return ($param->is_variadic ? '...' : '') . $type_string . ($param->is_optional ? '=' : '');
                    },
                    $this->params
                )
            ) . ')';
        }

        if ($this->return_type !== null) {
            $return_type_multiple = count($this->return_type->getAtomicTypes()) > 1;

            $return_type_string = ':' . ($return_type_multiple ? '(' : '') . $this->return_type->toNamespacedString(
                $namespace,
                $aliased_classes,
                $this_class,
                false
            ) . ($return_type_multiple ? ')' : '');
        }

        if ($this instanceof TNamedObject) {
            return parent::toNamespacedString($namespace, $aliased_classes, $this_class, true)
                . $param_string . $return_type_string;
        }

        return ($this->is_pure ? 'pure-' : '') . 'callable' . $param_string . $return_type_string;
    }

    /**
     * @param  array<string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): string {
        if ($this instanceof TNamedObject) {
            return parent::toNamespacedString($namespace, $aliased_classes, $this_class, true);
        }

        return $this->value;
    }

    public function getId(bool $nested = false): string
    {
        $param_string = '';
        $return_type_string = '';

        if ($this->params !== null) {
            $param_string .= '(';
            foreach ($this->params as $i => $param) {
                if ($i) {
                    $param_string .= ', ';
                }

                $param_string .= $param->getId();
            }

            $param_string .= ')';
        }

        if ($this->return_type !== null) {
            $return_type_multiple = count($this->return_type->getAtomicTypes()) > 1;
            $return_type_string = ':' . ($return_type_multiple ? '(' : '')
                . $this->return_type->getId() . ($return_type_multiple ? ')' : '');
        }

        return ($this->is_pure ? 'pure-' : ($this->is_pure === null ? '' : 'impure-'))
            . $this->value . $param_string . $return_type_string;
    }

    public function __toString(): string
    {
        return $this->getId();
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
        $callable = clone $this;

        if ($callable->params) {
            foreach ($callable->params as $offset => $param) {
                $input_param_type = null;

                if (($input_type instanceof Atomic\TClosure || $input_type instanceof Atomic\TCallable)
                    && isset($input_type->params[$offset])
                ) {
                    $input_param_type = $input_type->params[$offset]->type;
                }

                if (!$param->type) {
                    continue;
                }

                $param->type = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                    $param->type,
                    $template_result,
                    $codebase,
                    $statements_analyzer,
                    $input_param_type,
                    $input_arg_offset,
                    $calling_class,
                    $calling_function,
                    $replace,
                    !$add_upper_bound,
                    $depth
                );
            }
        }

        if (($input_type instanceof Atomic\TCallable || $input_type instanceof Atomic\TClosure)
            && $callable->return_type
            && $input_type->return_type
        ) {
            $callable->return_type = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                $callable->return_type,
                $template_result,
                $codebase,
                $statements_analyzer,
                $input_type->return_type,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_upper_bound
            );
        }

        return $callable;
    }

    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ) : void {
        if ($this->params) {
            foreach ($this->params as $param) {
                if (!$param->type) {
                    continue;
                }

                $param->type->replaceTemplateTypesWithArgTypes($template_result, $codebase);
            }
        }

        if ($this->return_type) {
            $this->return_type->replaceTemplateTypesWithArgTypes($template_result, $codebase);
        }
    }

    /**
     * @return list<\Psalm\Type\TypeNode>
     */
    public function getChildNodes() : array
    {
        $child_nodes = [];

        if ($this->params) {
            foreach ($this->params as $param) {
                if ($param->type) {
                    $child_nodes[] = $param->type;
                }
            }
        }

        if ($this->return_type) {
            $child_nodes[] = $this->return_type;
        }

        return $child_nodes;
    }
}
