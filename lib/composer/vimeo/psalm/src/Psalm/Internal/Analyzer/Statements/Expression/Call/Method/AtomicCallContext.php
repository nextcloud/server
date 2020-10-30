<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Provider\NodeDataProvider;
use PhpParser;

class AtomicCallContext
{
    /** @var MethodIdentifier */
    public $method_id;

    /** @var array<int, PhpParser\Node\Arg> */
    public $args;

    /** @var NodeDataProvider */
    public $node_data;

    /** @param list<PhpParser\Node\Arg> $args */
    public function __construct(MethodIdentifier $method_id, array $args, NodeDataProvider $node_data)
    {
        $this->method_id = $method_id;
        $this->args = $args;
        $this->node_data = $node_data;
    }
}
