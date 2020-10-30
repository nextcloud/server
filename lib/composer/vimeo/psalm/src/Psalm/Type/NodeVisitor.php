<?php
namespace Psalm\Type;

abstract class NodeVisitor
{
    public const STOP_TRAVERSAL = 1;
    public const DONT_TRAVERSE_CHILDREN = 2;

    /**
     * @return self::STOP_TRAVERSAL|self::DONT_TRAVERSE_CHILDREN|null
     */
    abstract protected function enterNode(TypeNode $type) : ?int;

    /**
     * @return bool - true if we want to continue traversal, false otherwise
     */
    public function traverse(TypeNode $node) : bool
    {
        $visitor_result = $this->enterNode($node);

        if ($visitor_result === self::DONT_TRAVERSE_CHILDREN) {
            return true;
        }

        if ($visitor_result === self::STOP_TRAVERSAL) {
            return false;
        }

        foreach ($node->getChildNodes() as $child_node) {
            if ($this->traverse($child_node) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<TypeNode> $nodes
     */
    public function traverseArray(array $nodes) : void
    {
        foreach ($nodes as $node) {
            if ($this->traverse($node) === false) {
                return;
            }
        }
    }
}
