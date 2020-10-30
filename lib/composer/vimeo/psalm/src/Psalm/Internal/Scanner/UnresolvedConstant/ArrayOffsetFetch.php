<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 */
class ArrayOffsetFetch extends UnresolvedConstantComponent
{
    /** @var UnresolvedConstantComponent */
    public $array;

    /** @var UnresolvedConstantComponent */
    public $offset;

    public function __construct(UnresolvedConstantComponent $left, UnresolvedConstantComponent $right)
    {
        $this->array = $left;
        $this->offset = $right;
    }
}
