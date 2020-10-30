<?php
namespace Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class TemplateAsTree extends \Psalm\Internal\Type\ParseTree
{
    /**
     * @var string
     */
    public $param_name;

    /**
     * @var string
     */
    public $as;

    public function __construct(string $param_name, string $as, ?\Psalm\Internal\Type\ParseTree $parent = null)
    {
        $this->param_name = $param_name;
        $this->as = $as;
        $this->parent = $parent;
    }
}
