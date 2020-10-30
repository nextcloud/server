<?php

namespace Psalm\Internal\Provider;

use PhpParser;
use SplObjectStorage;
use Psalm\Type\Union;

class NodeDataProvider implements \Psalm\NodeTypeProvider
{
    /** @var SplObjectStorage<PhpParser\Node, Union> */
    private $node_types;

    /** @var SplObjectStorage<PhpParser\Node, array<string, non-empty-list<non-empty-list<string>>>|null> */
    private $node_assertions;

    /** @var SplObjectStorage<PhpParser\Node, array<int, \Psalm\Storage\Assertion>> */
    private $node_if_true_assertions;

    /** @var SplObjectStorage<PhpParser\Node, array<int, \Psalm\Storage\Assertion>> */
    private $node_if_false_assertions;

    /** @var bool */
    public $cache_assertions = true;

    public function __construct()
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->node_types = new SplObjectStorage();
        /** @psalm-suppress PropertyTypeCoercion */
        $this->node_assertions = new SplObjectStorage();
        /** @psalm-suppress PropertyTypeCoercion */
        $this->node_if_true_assertions = new SplObjectStorage();
        /** @psalm-suppress PropertyTypeCoercion */
        $this->node_if_false_assertions = new SplObjectStorage();
    }

    /**
     * @param PhpParser\Node\Expr|PhpParser\Node\Name|PhpParser\Node\Stmt\Return_ $node
     */
    public function setType(PhpParser\NodeAbstract $node, Union $type) : void
    {
        $this->node_types[$node] = $type;
    }

    /**
     * @param PhpParser\Node\Expr|PhpParser\Node\Name|PhpParser\Node\Stmt\Return_ $node
     */
    public function getType(PhpParser\NodeAbstract $node) : ?Union
    {
        return $this->node_types[$node] ?? null;
    }

    /**
     * @param array<string, non-empty-list<non-empty-list<string>>>|null $assertions
     */
    public function setAssertions(PhpParser\Node\Expr $node, ?array $assertions) : void
    {
        if (!$this->cache_assertions) {
            return;
        }

        $this->node_assertions[$node] = $assertions;
    }

    /**
     * @return array<string, non-empty-list<non-empty-list<string>>>|null
     */
    public function getAssertions(PhpParser\Node\Expr $node) : ?array
    {
        if (!$this->cache_assertions) {
            return null;
        }

        return $this->node_assertions[$node] ?? null;
    }

    /**
     * @param PhpParser\Node\Expr\FuncCall|PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $node
     * @param array<int, \Psalm\Storage\Assertion> $assertions
     */
    public function setIfTrueAssertions(PhpParser\Node\Expr $node, array $assertions) : void
    {
        $this->node_if_true_assertions[$node] = $assertions;
    }

    /**
     * @param PhpParser\Node\Expr\FuncCall|PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $node
     * @return array<int, \Psalm\Storage\Assertion>|null
     */
    public function getIfTrueAssertions(PhpParser\Node\Expr $node) : ?array
    {
        return $this->node_if_true_assertions[$node] ?? null;
    }

    /**
     * @param PhpParser\Node\Expr\FuncCall|PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $node
     * @param array<int, \Psalm\Storage\Assertion> $assertions
     */
    public function setIfFalseAssertions(PhpParser\Node\Expr $node, array $assertions) : void
    {
        $this->node_if_false_assertions[$node] = $assertions;
    }

    /**
     * @param PhpParser\Node\Expr\FuncCall|PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $node
     * @return array<int, \Psalm\Storage\Assertion>|null
     */
    public function getIfFalseAssertions(PhpParser\Node\Expr $node) : ?array
    {
        return $this->node_if_false_assertions[$node] ?? null;
    }

    public function isPureCompatible(PhpParser\Node\Expr $node) : bool
    {
        $node_type = self::getType($node);

        return ($node_type && $node_type->reference_free) || isset($node->pure);
    }

    public function clearNodeOfTypeAndAssertions(PhpParser\Node\Expr $node) : void
    {
        unset($this->node_types[$node], $this->node_assertions[$node]);
    }
}
