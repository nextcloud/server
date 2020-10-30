<?php
namespace Psalm\Internal\Type;

/**
 * @internal
 */
class ParseTree
{
    /**
     * @var list<ParseTree>
     */
    public $children = [];

    /**
     * @var null|ParseTree
     */
    public $parent;

    /**
     * @var bool
     */
    public $possibly_undefined = false;

    public function __construct(?ParseTree $parent = null)
    {
        $this->parent = $parent;
    }

    public function __destruct()
    {
        $this->parent = null;
    }

    public function cleanParents() : void
    {
        foreach ($this->children as $child) {
            $child->cleanParents();
        }

        $this->parent = null;
    }
}
