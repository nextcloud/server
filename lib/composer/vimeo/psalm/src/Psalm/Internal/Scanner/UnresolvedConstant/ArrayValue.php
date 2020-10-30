<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 */
class ArrayValue extends UnresolvedConstantComponent
{
    /** @var array<int, KeyValuePair> */
    public $entries;

    /** @param list<KeyValuePair> $entries */
    public function __construct(array $entries)
    {
        $this->entries = $entries;
    }
}
