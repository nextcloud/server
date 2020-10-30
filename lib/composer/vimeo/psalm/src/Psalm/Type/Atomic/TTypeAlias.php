<?php
namespace Psalm\Type\Atomic;

use function implode;
use function array_map;

class TTypeAlias extends \Psalm\Type\Atomic
{
    /**
     * @var array<string, TTypeAlias>|null
     */
    public $extra_types;

    /** @var string */
    public $declaring_fq_classlike_name;

    /** @var string */
    public $alias_name;

    public function __construct(string $declaring_fq_classlike_name, string $alias_name)
    {
        $this->declaring_fq_classlike_name = $declaring_fq_classlike_name;
        $this->alias_name = $alias_name;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'type-alias(' . $this->declaring_fq_classlike_name . '::' . $this->alias_name . ')';
    }

    public function __toString(): string
    {
        if ($this->extra_types) {
            return $this->getKey() . '&' . implode(
                '&',
                array_map(
                    'strval',
                    $this->extra_types
                )
            );
        }

        return $this->getKey();
    }

    public function getId(bool $nested = false): string
    {
        if ($this->extra_types) {
            return $this->getKey() . '&' . implode(
                '&',
                array_map(
                    function ($type) {
                        return $type->getId(true);
                    },
                    $this->extra_types
                )
            );
        }

        return $this->getKey();
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

    /**
     * @param  array<string, string> $aliased_classes
     *
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        return $this->getKey();
    }

    public function getAssertionString(): string
    {
        return 'mixed';
    }
}
