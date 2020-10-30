<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 */
class Constant extends UnresolvedConstantComponent
{
    /** @var string */
    public $name;

    /** @var bool */
    public $is_fully_qualified;

    public function __construct(string $name, bool $is_fully_qualified)
    {
        $this->name = $name;
        $this->is_fully_qualified = $is_fully_qualified;
    }
}
