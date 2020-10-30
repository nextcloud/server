<?php
namespace Psalm\Type\Atomic;

use function count;
use function get_class;
use Psalm\Type\Atomic;

/**
 * Represents an array with generic type parameters.
 */
class TArray extends \Psalm\Type\Atomic
{
    use GenericTrait;

    /**
     * @var string
     */
    public $value = 'array';

    /**
     * Constructs a new instance of a generic type
     *
     * @param non-empty-list<\Psalm\Type\Union> $type_params
     */
    public function __construct(array $type_params)
    {
        $this->type_params = $type_params;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'array';
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
        return $this->getKey();
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return $this->type_params[0]->isArrayKey() && $this->type_params[1]->isMixed();
    }

    public function equals(Atomic $other_type): bool
    {
        if (get_class($other_type) !== static::class) {
            return false;
        }

        if ($this instanceof TNonEmptyArray
            && $other_type instanceof TNonEmptyArray
            && $this->count !== $other_type->count
        ) {
            return false;
        }

        if (count($this->type_params) !== count($other_type->type_params)) {
            return false;
        }

        foreach ($this->type_params as $i => $type_param) {
            if (!$type_param->equals($other_type->type_params[$i])) {
                return false;
            }
        }

        return true;
    }

    public function getAssertionString(): string
    {
        return $this->getKey();
    }
}
