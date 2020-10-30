<?php
declare(strict_types=1);
namespace Psalm\Internal\PhpVisitor;

use function array_map;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Visitor cloning all nodes and linking to the original nodes using an attribute.
 *
 * This visitor is required to perform format-preserving pretty prints.
 */
class CloningVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $node): Node
    {
        $node = clone $node;
        if ($cs = $node->getComments()) {
            $node->setAttribute(
                'comments',
                array_map(
                    /**
                     * @return \PhpParser\Comment
                     */
                    function (\PhpParser\Comment $c): \PhpParser\Comment {
                        return clone $c;
                    },
                    $cs
                )
            );
        }

        return $node;
    }
}
