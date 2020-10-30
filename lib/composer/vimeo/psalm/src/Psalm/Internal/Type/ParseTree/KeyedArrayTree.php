<?php
namespace Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class KeyedArrayTree extends \Psalm\Internal\Type\ParseTree
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var bool
     */
    public $terminated = false;

    public function __construct(string $value, ?\Psalm\Internal\Type\ParseTree $parent = null)
    {
        $this->value = $value;
        $this->parent = $parent;
    }
}
