<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\CertificationPath\Policy;

use LogicException;
use SpomkyLabs\Pki\X509\Certificate\Certificate;
use SpomkyLabs\Pki\X509\Certificate\Extension\CertificatePolicy\PolicyInformation;
use SpomkyLabs\Pki\X509\CertificationPath\PathValidation\ValidatorState;
use function count;
use function in_array;

final class PolicyTree
{
    /**
     * @param PolicyNode $root Initial root node
     */
    private function __construct(
        private ?PolicyNode $root
    ) {
    }

    public static function create(?PolicyNode $root): self
    {
        return new self($root);
    }

    /**
     * Process policy information from the certificate.
     *
     * Certificate policies extension must be present.
     */
    public function processPolicies(ValidatorState $state, Certificate $cert): ValidatorState
    {
        $policies = $cert->tbsCertificate()
            ->extensions()
            ->certificatePolicies();
        $tree = clone $this;
        // (d.1) for each policy P not equal to anyPolicy
        foreach ($policies as $policy) {
            /** @var PolicyInformation $policy */
            if ($policy->isAnyPolicy()) {
                $tree->processAnyPolicy($policy, $cert, $state);
            } else {
                $tree->processPolicy($policy, $state);
            }
        }
        // if whole tree is pruned
        if ($tree->pruneTree($state->index() - 1) === 0) {
            return $state->withoutValidPolicyTree();
        }
        return $state->withValidPolicyTree($tree);
    }

    /**
     * Process policy mappings from the certificate.
     */
    public function processMappings(ValidatorState $state, Certificate $cert): ValidatorState
    {
        $tree = clone $this;
        if ($state->policyMapping() > 0) {
            $tree->_applyMappings($cert, $state);
        } elseif ($state->policyMapping() === 0) {
            $tree->_deleteMappings($cert, $state);
        }
        // if whole tree is pruned
        if ($tree->root === null) {
            return $state->withoutValidPolicyTree();
        }
        return $state->withValidPolicyTree($tree);
    }

    /**
     * Calculate policy intersection as specified in Wrap-Up Procedure 6.1.5.g.
     *
     * @param array<string> $policies
     */
    public function calculateIntersection(ValidatorState $state, array $policies): ValidatorState
    {
        $tree = clone $this;
        $valid_policy_node_set = $tree->validPolicyNodeSet();
        // 2. If the valid_policy of any node in the valid_policy_node_set
        // is not in the user-initial-policy-set and is not anyPolicy,
        // delete this node and all its children.
        $valid_policy_node_set = array_filter(
            $valid_policy_node_set,
            function (PolicyNode $node) use ($policies) {
                if ($node->isAnyPolicy()) {
                    return true;
                }
                if (in_array($node->validPolicy(), $policies, true)) {
                    return true;
                }
                $node->remove();
                return false;
            }
        );
        // array of valid policy OIDs
        $valid_policy_set = array_map(static fn (PolicyNode $node) => $node->validPolicy(), $valid_policy_node_set);
        // 3. If the valid_policy_tree includes a node of depth n with
        // the valid_policy anyPolicy and the user-initial-policy-set
        // is not any-policy
        foreach ($tree->nodesAtDepth($state->index()) as $node) {
            if ($node->hasParent() && $node->isAnyPolicy()) {
                // a. Set P-Q to the qualifier_set in the node of depth n
                // with valid_policy anyPolicy.
                $pq = $node->qualifiers();
                // b. For each P-OID in the user-initial-policy-set that is not
                // the valid_policy of a node in the valid_policy_node_set,
                // create a child node whose parent is the node of depth n-1
                // with the valid_policy anyPolicy.
                $poids = array_diff($policies, $valid_policy_set);
                foreach ($tree->nodesAtDepth($state->index() - 1) as $parent) {
                    if ($parent->isAnyPolicy()) {
                        // Set the values in the child node as follows:
                        // set the valid_policy to P-OID, set the qualifier_set
                        // to P-Q, and set the expected_policy_set to {P-OID}.
                        foreach ($poids as $poid) {
                            $parent->addChild(PolicyNode::create($poid, $pq, [$poid]));
                        }
                        break;
                    }
                }
                // c. Delete the node of depth n with the
                // valid_policy anyPolicy.
                $node->remove();
            }
        }
        // 4. If there is a node in the valid_policy_tree of depth n-1 or less
        // without any child nodes, delete that node. Repeat this step until
        // there are no nodes of depth n-1 or less without children.
        if ($tree->pruneTree($state->index() - 1) === 0) {
            return $state->withoutValidPolicyTree();
        }
        return $state->withValidPolicyTree($tree);
    }

    /**
     * Get policies at given policy tree depth.
     *
     * @param int $i Depth in range 1..n
     *
     * @return PolicyInformation[]
     */
    public function policiesAtDepth(int $i): array
    {
        $policies = [];
        foreach ($this->nodesAtDepth($i) as $node) {
            $policies[] = PolicyInformation::create($node->validPolicy(), ...$node->qualifiers());
        }
        return $policies;
    }

    /**
     * Process single policy information.
     */
    private function processPolicy(PolicyInformation $policy, ValidatorState $state): void
    {
        $p_oid = $policy->oid();
        $i = $state->index();
        $match_count = 0;
        // (d.1.i) for each node of depth i-1 in the valid_policy_tree...
        foreach ($this->nodesAtDepth($i - 1) as $node) {
            // ...where P-OID is in the expected_policy_set
            if ($node->hasExpectedPolicy($p_oid)) {
                $node->addChild(PolicyNode::create($p_oid, $policy->qualifiers(), [$p_oid]));
                ++$match_count;
            }
        }
        // (d.1.ii) if there was no match in step (i)...
        if ($match_count === 0) {
            // ...and the valid_policy_tree includes a node of depth i-1 with
            // the valid_policy anyPolicy
            foreach ($this->nodesAtDepth($i - 1) as $node) {
                if ($node->isAnyPolicy()) {
                    $node->addChild(PolicyNode::create($p_oid, $policy->qualifiers(), [$p_oid]));
                }
            }
        }
    }

    /**
     * Process anyPolicy policy information.
     */
    private function processAnyPolicy(PolicyInformation $policy, Certificate $cert, ValidatorState $state): void
    {
        $i = $state->index();
        // if (a) inhibit_anyPolicy is greater than 0 or
        // (b) i<n and the certificate is self-issued
        if (! ($state->inhibitAnyPolicy() > 0 ||
            ($i < $state->pathLength() && $cert->isSelfIssued()))) {
            return;
        }
        // for each node in the valid_policy_tree of depth i-1
        foreach ($this->nodesAtDepth($i - 1) as $node) {
            // for each value in the expected_policy_set
            foreach ($node->expectedPolicies() as $p_oid) {
                // that does not appear in a child node
                if (! $node->hasChildWithValidPolicy($p_oid)) {
                    $node->addChild(PolicyNode::create($p_oid, $policy->qualifiers(), [$p_oid]));
                }
            }
        }
    }

    /**
     * Apply policy mappings to the policy tree.
     */
    private function _applyMappings(Certificate $cert, ValidatorState $state): void
    {
        $policy_mappings = $cert->tbsCertificate()
            ->extensions()
            ->policyMappings();
        // (6.1.4. b.1.) for each node in the valid_policy_tree of depth i...
        foreach ($policy_mappings->flattenedMappings() as $idp => $sdps) {
            $match_count = 0;
            foreach ($this->nodesAtDepth($state->index()) as $node) {
                // ...where ID-P is the valid_policy
                if ($node->validPolicy() === $idp) {
                    // set expected_policy_set to the set of subjectDomainPolicy
                    // values that are specified as equivalent to ID-P by
                    // the policy mappings extension
                    $node->setExpectedPolicies(...$sdps);
                    ++$match_count;
                }
            }
            // if no node of depth i in the valid_policy_tree has
            // a valid_policy of ID-P...
            if ($match_count === 0) {
                $this->_applyAnyPolicyMapping($cert, $state, $idp, $sdps);
            }
        }
    }

    /**
     * Apply anyPolicy mapping to the policy tree as specified in 6.1.4 (b)(1).
     *
     * @param string $idp OID of the issuer domain policy
     * @param array<string> $sdps Array of subject domain policy OIDs
     */
    private function _applyAnyPolicyMapping(
        Certificate $cert,
        ValidatorState $state,
        string $idp,
        array $sdps
    ): void {
        // (6.1.4. b.1.) ...but there is a node of depth i with
        // a valid_policy of anyPolicy
        foreach ($this->nodesAtDepth($state->index()) as $node) {
            if ($node->isAnyPolicy()) {
                // then generate a child node of the node of depth i-1
                // that has a valid_policy of anyPolicy as follows...
                foreach ($this->nodesAtDepth($state->index() - 1) as $subnode) {
                    if ($subnode->isAnyPolicy()) {
                        // try to fetch qualifiers of anyPolicy certificate policy
                        try {
                            $qualifiers = $cert->tbsCertificate()
                                ->extensions()
                                ->certificatePolicies()
                                ->anyPolicy()
                                ->qualifiers();
                        } catch (LogicException) {
                            // if there's no policies or no qualifiers
                            $qualifiers = [];
                        }
                        $subnode->addChild(PolicyNode::create($idp, $qualifiers, $sdps));
                        // bail after first anyPolicy has been processed
                        break;
                    }
                }
                // bail after first anyPolicy has been processed
                break;
            }
        }
    }

    /**
     * Delete nodes as specified in 6.1.4 (b)(2).
     */
    private function _deleteMappings(Certificate $cert, ValidatorState $state): void
    {
        $idps = $cert->tbsCertificate()
            ->extensions()
            ->policyMappings()
            ->issuerDomainPolicies();
        // delete each node of depth i in the valid_policy_tree
        // where ID-P is the valid_policy
        foreach ($this->nodesAtDepth($state->index()) as $node) {
            if (in_array($node->validPolicy(), $idps, true)) {
                $node->remove();
            }
        }
        $this->pruneTree($state->index() - 1);
    }

    /**
     * Prune tree starting from given depth.
     *
     * @return int The number of nodes left in a tree
     */
    private function pruneTree(int $depth): int
    {
        if ($this->root === null) {
            return 0;
        }
        for ($i = $depth; $i > 0; --$i) {
            foreach ($this->nodesAtDepth($i) as $node) {
                if (count($node) === 0) {
                    $node->remove();
                }
            }
        }
        // if root has no children left
        if (count($this->root) === 0) {
            $this->root = null;
            return 0;
        }
        return $this->root->nodeCount();
    }

    /**
     * Get all nodes at given depth.
     *
     * @return PolicyNode[]
     */
    private function nodesAtDepth(int $i): array
    {
        if ($this->root === null) {
            return [];
        }
        $depth = 0;
        $nodes = [$this->root];
        while ($depth < $i) {
            $nodes = self::gatherChildren(...$nodes);
            if (count($nodes) === 0) {
                break;
            }
            ++$depth;
        }
        return $nodes;
    }

    /**
     * Get the valid policy node set as specified in spec 6.1.5.(g)(iii)1.
     *
     * @return PolicyNode[]
     */
    private function validPolicyNodeSet(): array
    {
        // 1. Determine the set of policy nodes whose parent nodes have
        // a valid_policy of anyPolicy. This is the valid_policy_node_set.
        $set = [];
        if ($this->root === null) {
            return $set;
        }
        // for each node in a tree
        $this->root->walkNodes(
            function (PolicyNode $node) use (&$set) {
                $parents = $node->parents();
                // node has parents
                if (count($parents) !== 0) {
                    // check that each ancestor is an anyPolicy node
                    foreach ($parents as $ancestor) {
                        if (! $ancestor->isAnyPolicy()) {
                            return;
                        }
                    }
                    $set[] = $node;
                }
            }
        );
        return $set;
    }

    /**
     * Gather all children of given nodes to a flattened array.
     *
     * @return PolicyNode[]
     */
    private static function gatherChildren(PolicyNode ...$nodes): array
    {
        $children = [];
        foreach ($nodes as $node) {
            $children = array_merge($children, $node->children());
        }
        return $children;
    }
}
