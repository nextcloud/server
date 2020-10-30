<?php
namespace Psalm\Type\Atomic;

use function preg_quote;
use function preg_replace;
use function stripos;
use function strpos;
use function strtolower;

class TLiteralClassString extends TLiteralString
{
    /**
     * @param string $value string
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return 'class-string';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'class-string(' . $this->value . ')';
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
    ): string {
        return 'string';
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }

    public function getId(bool $nested = false): string
    {
        return $this->value . '::class';
    }

    public function getAssertionString(): string
    {
        return $this->getKey();
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
        if ($this->value === 'static') {
            return 'static::class';
        }

        if ($this->value === $this_class) {
            return 'self::class';
        }

        if ($namespace && stripos($this->value, $namespace . '\\') === 0) {
            return preg_replace(
                '/^' . preg_quote($namespace . '\\') . '/i',
                '',
                $this->value
            ) . '::class';
        }

        if (!$namespace && strpos($this->value, '\\') === false) {
            return $this->value . '::class';
        }

        if (isset($aliased_classes[strtolower($this->value)])) {
            return $aliased_classes[strtolower($this->value)] . '::class';
        }

        return '\\' . $this->value . '::class';
    }
}
