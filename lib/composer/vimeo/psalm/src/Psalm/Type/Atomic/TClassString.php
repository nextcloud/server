<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\UnionTemplateHandler;
use Psalm\Type\Atomic;
use function preg_quote;
use function preg_replace;
use function stripos;
use function strpos;
use function strtolower;

class TClassString extends TString
{
    /**
     * @var string
     */
    public $as;

    /**
     * @var ?TNamedObject
     */
    public $as_type;

    public function __construct(string $as = 'object', ?TNamedObject $as_type = null)
    {
        $this->as = $as;
        $this->as_type = $as_type;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'class-string' . ($this->as === 'object' ? '' : '<' . $this->as_type . '>');
    }

    public function __toString(): string
    {
        return $this->getKey();
    }

    public function getId(bool $nested = false): string
    {
        return $this->getKey();
    }

    public function getAssertionString(): string
    {
        return 'class-string';
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
    ): ?string {
        return 'string';
    }

    /**
     * @param  array<string> $aliased_classes
     *
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        if ($this->as === 'object') {
            return 'class-string';
        }

        if ($namespace && stripos($this->as, $namespace . '\\') === 0) {
            return 'class-string<' . preg_replace(
                '/^' . preg_quote($namespace . '\\') . '/i',
                '',
                $this->as
            ) . '>';
        }

        if (!$namespace && strpos($this->as, '\\') === false) {
            return 'class-string<' . $this->as . '>';
        }

        if (isset($aliased_classes[strtolower($this->as)])) {
            return 'class-string<' . $aliased_classes[strtolower($this->as)] . '>';
        }

        return 'class-string<\\' . $this->as . '>';
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }

    public function getChildNodes() : array
    {
        return $this->as_type ? [$this->as_type] : [];
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
        $class_string = clone $this;

        if (!$class_string->as_type) {
            return $class_string;
        }

        if ($input_type instanceof TLiteralClassString) {
            $input_object_type = new TNamedObject($input_type->value);
        } elseif ($input_type instanceof TClassString && $input_type->as_type) {
            $input_object_type = $input_type->as_type;
        } else {
            $input_object_type = new TObject();
        }

        $as_type = UnionTemplateHandler::replaceTemplateTypesWithStandins(
            new \Psalm\Type\Union([$class_string->as_type]),
            $template_result,
            $codebase,
            $statements_analyzer,
            new \Psalm\Type\Union([$input_object_type]),
            $input_arg_offset,
            $calling_class,
            $calling_function,
            $replace,
            $add_upper_bound,
            $depth
        );

        $as_type_types = \array_values($as_type->getAtomicTypes());

        $class_string->as_type = \count($as_type_types) === 1
            && $as_type_types[0] instanceof TNamedObject
            ? $as_type_types[0]
            : null;

        if (!$class_string->as_type) {
            $class_string->as = 'object';
        }

        return $class_string;
    }
}
