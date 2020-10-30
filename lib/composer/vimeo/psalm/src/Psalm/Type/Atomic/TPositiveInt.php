<?php
namespace Psalm\Type\Atomic;

class TPositiveInt extends TInt
{
    public function getId(bool $nested = false): string
    {
        return 'positive-int';
    }

    public function __toString(): string
    {
        return 'positive-int';
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
        return $use_phpdoc_format ? 'int' : 'positive-int';
    }
}
