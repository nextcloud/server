<?php
namespace Psalm\Type\Atomic;

class TClosedResource extends \Psalm\Type\Atomic
{
    public function __toString(): string
    {
        return 'closed-resource';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'closed-resource';
    }

    public function getId(bool $nested = false): string
    {
        return 'closed-resource';
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
}
