<?php
namespace Psalm\Type\Atomic;

class TLiteralInt extends TInt
{
    /** @var int */
    public $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'int(' . $this->value . ')';
    }

    public function getId(bool $nested = false): string
    {
        return 'int(' . $this->value . ')';
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
        return $php_major_version >= 7 ? 'int' : null;
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
        return $use_phpdoc_format ? 'int' : (string) $this->value;
    }
}
