<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\CertificationPath\Policy;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use SpomkyLabs\Pki\X509\Certificate\Extension\CertificatePolicy\PolicyInformation;
use SpomkyLabs\Pki\X509\Certificate\Extension\CertificatePolicy\PolicyQualifierInfo;
use function count;
use function in_array;

/**
 * Policy node class for certification path validation.
 *
 * @internal Mutable class used by PolicyTree
 *
 * @see https://tools.ietf.org/html/rfc5280#section-6.1.2
 */
final class PolicyNode implements IteratorAggregate, Countable
{
    /**
     * List of child nodes.
     *
     * @var PolicyNode[]
     */
    private array $children;

    /**
     * Reference to the parent node.
     */
    private PolicyNode|null $parent = null;

    /**
     * @param PolicyQualifierInfo[] $qualifiers
     * @param string[] $expectedPolicies
     */
    private function __construct(
        private readonly string $validPolicy,
        private readonly array $qualifiers,
        private array $expectedPolicies
    ) {
        $this->children = [];
    }

    /**
     * @param PolicyQualifierInfo[] $qualifiers
     * @param string[] $expectedPolicies
     */
    public static function create(string $validPolicy, array $qualifiers, array $expectedPolicies): self
    {
        return new self($validPolicy, $qualifiers, $expectedPolicies);
    }

    /**
     * Create initial node for the policy tree.
     */
    public static function anyPolicyNode(): self
    {
        return self::create(PolicyInformation::OID_ANY_POLICY, [], [PolicyInformation::OID_ANY_POLICY]);
    }

    /**
     * Get the valid policy OID.
     */
    public function validPolicy(): string
    {
        return $this->validPolicy;
    }

    /**
     * Check whether node has anyPolicy as a valid policy.
     */
    public function isAnyPolicy(): bool
    {
        return $this->validPolicy === PolicyInformation::OID_ANY_POLICY;
    }

    /**
     * Get the qualifier set.
     *
     * @return PolicyQualifierInfo[]
     */
    public function qualifiers(): array
    {
        return $this->qualifiers;
    }

    /**
     * Check whether node has OID as an expected policy.
     */
    public function hasExpectedPolicy(string $oid): bool
    {
        return in_array($oid, $this->expectedPolicies, true);
    }

    /**
     * Get the expected policy set.
     *
     * @return string[]
     */
    public function expectedPolicies(): array
    {
        return $this->expectedPolicies;
    }

    /**
     * Set expected policies.
     *
     * @param string ...$oids Policy OIDs
     */
    public function setExpectedPolicies(string ...$oids): void
    {
        $this->expectedPolicies = $oids;
    }

    /**
     * Check whether node has a child node with given valid policy OID.
     */
    public function hasChildWithValidPolicy(string $oid): bool
    {
        foreach ($this->children as $node) {
            if ($node->validPolicy() === $oid) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add child node.
     */
    public function addChild(self $node): self
    {
        $id = spl_object_hash($node);
        $node->parent = $this;
        $this->children[$id] = $node;
        return $this;
    }

    /**
     * Get the child nodes.
     *
     * @return PolicyNode[]
     */
    public function children(): array
    {
        return array_values($this->children);
    }

    /**
     * Remove this node from the tree.
     *
     * @return self The removed node
     */
    public function remove(): self
    {
        if ($this->parent !== null) {
            $id = spl_object_hash($this);
            unset($this->parent->children[$id], $this->parent);
        }
        return $this;
    }

    /**
     * Check whether node has a parent.
     */
    public function hasParent(): bool
    {
        return isset($this->parent);
    }

    /**
     * Get the parent node.
     */
    public function parent(): ?self
    {
        return $this->parent;
    }

    /**
     * Get chain of parent nodes from this node's parent to the root node.
     *
     * @return PolicyNode[]
     */
    public function parents(): array
    {
        if ($this->parent === null) {
            return [];
        }
        $nodes = $this->parent->parents();
        $nodes[] = $this->parent;
        return array_reverse($nodes);
    }

    /**
     * Walk tree from this node, applying a callback for each node.
     *
     * Nodes are traversed depth-first and callback shall be applied post-order.
     */
    public function walkNodes(callable $fn): void
    {
        foreach ($this->children as $node) {
            $node->walkNodes($fn);
        }
        $fn($this);
    }

    /**
     * Get the total number of nodes in a tree.
     */
    public function nodeCount(): int
    {
        $c = 1;
        foreach ($this->children as $child) {
            $c += $child->nodeCount();
        }
        return $c;
    }

    /**
     * Get the number of child nodes.
     *
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->children);
    }

    /**
     * Get iterator for the child nodes.
     *
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->children);
    }
}
