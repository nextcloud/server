<?php
namespace Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class ConditionalTree extends \Psalm\Internal\Type\ParseTree
{
    /**
     * @var TemplateIsTree
     */
    public $condition;

    public function __construct(TemplateIsTree $condition, ?\Psalm\Internal\Type\ParseTree $parent = null)
    {
        $this->condition = $condition;
        $this->parent = $parent;
    }
}
