<?php
namespace Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class IndexedAccessTree extends \Psalm\Internal\Type\ParseTree
{
    /**
     * @var string
     */
    public $value;

    public function __construct(string $value, ?\Psalm\Internal\Type\ParseTree $parent = null)
    {
        $this->value = $value;
        $this->parent = $parent;
    }
}
