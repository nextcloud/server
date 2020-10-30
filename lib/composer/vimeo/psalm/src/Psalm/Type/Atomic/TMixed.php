<?php
namespace Psalm\Type\Atomic;

class TMixed extends \Psalm\Type\Atomic
{
    /** @var bool */
    public $from_loop_isset = false;

    public function __construct(bool $from_loop_isset = false)
    {
        $this->from_loop_isset = $from_loop_isset;
    }

    public function __toString(): string
    {
        return 'mixed';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'mixed';
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
        return 'mixed';
    }
}
