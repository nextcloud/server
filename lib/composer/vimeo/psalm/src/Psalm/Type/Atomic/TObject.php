<?php
namespace Psalm\Type\Atomic;

class TObject extends \Psalm\Type\Atomic
{
    public function __toString(): string
    {
        return 'object';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'object';
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
            ? $this->getKey()
            : null;
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return true;
    }
}
