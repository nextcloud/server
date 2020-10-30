<?php
declare(strict_types=1);
namespace Psalm\Internal\PhpVisitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class TypeMappingVisitor extends NodeVisitorAbstract
{
    private $fake_type_provider;
    private $real_type_provider;

    public function __construct(
        \Psalm\Internal\Provider\NodeDataProvider $fake_type_provider,
        \Psalm\Internal\Provider\NodeDataProvider $real_type_provider
    ) {
        $this->fake_type_provider = $fake_type_provider;
        $this->real_type_provider = $real_type_provider;
    }

    public function enterNode(Node $node): void
    {
        $origNode = $node;

        /** @psalm-suppress ArgumentTypeCoercion */
        $node_type = $this->fake_type_provider->getType($origNode);

        if ($node_type) {
            /** @psalm-suppress ArgumentTypeCoercion */
            $this->real_type_provider->setType($origNode, clone $node_type);
        }
    }
}
