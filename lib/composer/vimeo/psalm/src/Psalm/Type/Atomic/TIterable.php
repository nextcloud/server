<?php
namespace Psalm\Type\Atomic;

use function count;
use function implode;
use Psalm\Type\Atomic;
use function substr;
use function array_merge;

class TIterable extends Atomic
{
    use HasIntersectionTrait;
    use GenericTrait;

    /**
     * @var string
     */
    public $value = 'iterable';

    /**
     * @var bool
     */
    public $has_docblock_params = false;

    /**
     * @param list<\Psalm\Type\Union>     $type_params
     */
    public function __construct(array $type_params = [])
    {
        if ($type_params) {
            $this->has_docblock_params = true;
            $this->type_params = $type_params;
        } else {
            $this->type_params = [\Psalm\Type::getMixed(), \Psalm\Type::getMixed()];
        }
    }
    
    public function getKey(bool $include_extra = true): string
    {
        if ($include_extra && $this->extra_types) {
            // do nothing
        }

        return 'iterable';
    }

    public function getAssertionString(): string
    {
        return 'iterable';
    }

    public function getId(bool $nested = false): string
    {
        $s = '';
        foreach ($this->type_params as $type_param) {
            $s .= $type_param->getId() . ', ';
        }

        $extra_types = '';

        if ($this->extra_types) {
            $extra_types = '&' . implode('&', $this->extra_types);
        }

        return $this->value . '<' . substr($s, 0, -2) . '>' . $extra_types;
    }

    public function __toString(): string
    {
        return $this->getId();
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
            || ($php_major_version === 7 && $php_minor_version >= 1)
            ? 'iterable'
            : null;
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return $this->type_params[0]->isMixed() && $this->type_params[1]->isMixed();
    }

    public function equals(Atomic $other_type): bool
    {
        if (!$other_type instanceof self) {
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

    public function getChildNodes() : array
    {
        return array_merge($this->type_params, $this->extra_types !== null ? $this->extra_types : []);
    }
}
