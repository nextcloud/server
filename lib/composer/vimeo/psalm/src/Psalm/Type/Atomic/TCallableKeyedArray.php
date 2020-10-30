<?php
namespace Psalm\Type\Atomic;

/**
 * Represents a non-empty array
 */
class TCallableKeyedArray extends TKeyedArray
{
    public const KEY = 'callable-array';

    public function getKey(bool $include_extra = true): string
    {
        return 'array';
    }
}
