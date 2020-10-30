<?php
namespace Psalm\Type\Atomic;

use function preg_replace;
use function strlen;
use function substr;

class TLiteralString extends TString
{
    /** @var string */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getKey(bool $include_extra = true) : string
    {
        return $this->getId();
    }

    public function __toString(): string
    {
        return 'string';
    }

    public function getId(bool $nested = false): string
    {
        $no_newline_value = preg_replace("/\n/m", '\n', $this->value);
        if (strlen($this->value) > 80) {
            return 'string(' . substr($no_newline_value, 0, 80) . '...' . ')';
        }

        return 'string(' . $no_newline_value . ')';
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
        return $php_major_version >= 7 ? 'string' : null;
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
        return 'string';
    }
}
