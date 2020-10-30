<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 */
class ScalarValue extends UnresolvedConstantComponent
{
    /** @var string|int|float|bool|null */
    public $value;

    /** @param string|int|float|bool|null $value */
    public function __construct($value)
    {
        $this->value = $value;
    }
}
