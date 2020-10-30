<?php
namespace Psalm\Type\Atomic;

class TTemplateIndexedAccess extends \Psalm\Type\Atomic
{
    /**
     * @var string
     */
    public $array_param_name;

    /**
     * @var string
     */
    public $offset_param_name;

    /**
     * @var string
     */
    public $defining_class;

    public function __construct(
        string $array_param_name,
        string $offset_param_name,
        string $defining_class
    ) {
        $this->array_param_name = $array_param_name;
        $this->offset_param_name = $offset_param_name;
        $this->defining_class = $defining_class;
    }

    public function getKey(bool $include_extra = true): string
    {
        return $this->array_param_name . '[' . $this->offset_param_name . ']';
    }

    public function __toString(): string
    {
        return $this->getKey();
    }

    public function getId(bool $nested = false): string
    {
        return $this->getKey();
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
        return $this->getKey();
    }
}
