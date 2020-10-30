<?php
namespace Psalm\Type\Atomic;

class TCallable extends \Psalm\Type\Atomic
{
    use CallableTrait;

    /**
     * @var string
     */
    public $value;

    /**
     * @param  array<string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): string {
        return 'callable';
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return $this->params === null && $this->return_type === null;
    }
}
