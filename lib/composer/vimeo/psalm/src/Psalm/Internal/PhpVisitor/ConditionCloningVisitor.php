<?php
declare(strict_types=1);
namespace Psalm\Internal\PhpVisitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ConditionCloningVisitor extends NodeVisitorAbstract
{
    private $type_provider;

    public function __construct(\Psalm\Internal\Provider\NodeDataProvider $old_type_provider)
    {
        $this->type_provider = $old_type_provider;
    }

    /**
     * @return Node\Expr
     */
    public function enterNode(Node $node): Node
    {
        /** @var \PhpParser\Node\Expr $node */
        $origNode = $node;

        $node = clone $node;

        $node_type = $this->type_provider->getType($origNode);

        if ($node_type) {
            $this->type_provider->setType($node, clone $node_type);
        }

        return $node;
    }
}
