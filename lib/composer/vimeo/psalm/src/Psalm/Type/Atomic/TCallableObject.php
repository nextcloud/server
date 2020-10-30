<?php
namespace Psalm\Type\Atomic;

class TCallableObject extends TObject
{
    public function __toString(): string
    {
        return 'callable-object';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'callable-object';
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
        return $php_major_version > 7
            || ($php_major_version === 7 && $php_minor_version >= 2)
            ? 'object' : null;
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }

    public function getAssertionString(): string
    {
        return 'object';
    }
}
