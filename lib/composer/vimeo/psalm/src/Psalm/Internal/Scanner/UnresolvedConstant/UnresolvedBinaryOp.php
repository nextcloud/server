<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 */
abstract class UnresolvedBinaryOp extends UnresolvedConstantComponent
{
    /** @var UnresolvedConstantComponent */
    public $left;

    /** @var UnresolvedConstantComponent */
    public $right;

    public function __construct(UnresolvedConstantComponent $left, UnresolvedConstantComponent $right)
    {
        $this->left = $left;
        $this->right = $right;
    }
}
