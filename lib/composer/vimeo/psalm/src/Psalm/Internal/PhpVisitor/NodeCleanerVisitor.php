<?php
namespace Psalm\Internal\PhpVisitor;

use PhpParser;

/**
 * @internal
 */
class NodeCleanerVisitor extends PhpParser\NodeVisitorAbstract
{
    private $type_provider;

    public function __construct(\Psalm\Internal\Provider\NodeDataProvider $type_provider)
    {
        $this->type_provider = $type_provider;
    }

    public function enterNode(PhpParser\Node $node): ?int
    {
        if ($node instanceof PhpParser\Node\Expr) {
            $this->type_provider->clearNodeOfTypeAndAssertions($node);
        }

        return null;
    }
}
