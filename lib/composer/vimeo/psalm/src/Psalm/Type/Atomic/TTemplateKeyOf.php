<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Union;

class TTemplateKeyOf extends TArrayKey
{
    /**
     * @var string
     */
    public $param_name;

    /**
     * @var string
     */
    public $defining_class;

    /**
     * @var Union
     */
    public $as;

    public function __construct(
        string $param_name,
        string $defining_class,
        Union $as
    ) {
        $this->param_name = $param_name;
        $this->defining_class = $defining_class;
        $this->as = $as;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'key-of<' . $this->param_name . '>';
    }

    public function __toString(): string
    {
        return 'key-of<' . $this->param_name . '>';
    }

    public function getId(bool $nested = false): string
    {
        return 'key-of<' . $this->param_name . ':' . $this->defining_class . ' as ' . $this->as->getId() . '>';
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
        return null;
    }

    /**
     * @return false
     */
    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
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
        return 'key-of<' . $this->param_name . '>';
    }
}
