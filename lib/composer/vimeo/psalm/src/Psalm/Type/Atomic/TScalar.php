<?php
namespace Psalm\Type\Atomic;

class TScalar extends Scalar
{
    public function __toString(): string
    {
        return 'scalar';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'scalar';
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

    public function getAssertionString(): string
    {
        return 'scalar';
    }
}
