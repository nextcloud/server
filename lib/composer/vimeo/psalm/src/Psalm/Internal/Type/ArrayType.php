<?php declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Type;

/**
 * @internal
 */
class ArrayType
{
    /** @var Type\Union */
    public $key;

    /** @var Type\Union */
    public $value;

    /** @var bool */
    public $is_list;

    public function __construct(Type\Union $key, Type\Union $value, bool $is_list)
    {
        $this->key = $key;
        $this->value = $value;
        $this->is_list = $is_list;
    }

    /**
     * @return self|null
     */
    public static function infer(Type\Atomic $type): ?self
    {
        if ($type instanceof Type\Atomic\TKeyedArray) {
            return new self(
                $type->getGenericKeyType(),
                $type->getGenericValueType(),
                $type->is_list
            );
        }

        if ($type instanceof Type\Atomic\TList) {
            return new self(
                Type::getInt(),
                $type->type_param,
                true
            );
        }

        if ($type instanceof Type\Atomic\TArray) {
            return new self(
                $type->type_params[0],
                $type->type_params[1],
                false
            );
        }

        return null;
    }
}
