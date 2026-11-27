<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Nextcloud\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Removes `->setMaxResults()` and `->setFirstResult()` from IQueryBuilder chains
 * that contain an innerJoin or leftJoin.
 *
 * Using LIMIT and OFFSET on a JOIN query against partitioned tables causes:
 *   "Limit is not allowed in partitioned queries"
 *
 * These methods must be removed when joins are present to maintain
 * cross-database compatibility (MySQL, PostgreSQL, SQLite).
 */
final class RemoveSetMaxResultsFromJoinQueryRector extends AbstractRector {

	private const JOIN_METHODS = ['innerJoin', 'leftJoin'];
	private const LIMIT_METHODS = ['setMaxResults', 'setFirstResult'];

	public function getRuleDefinition(): RuleDefinition {
		return new RuleDefinition(
			'Remove setMaxResults() and setFirstResult() from IQueryBuilder chains with innerJoin/leftJoin to avoid "Limit is not allowed in partitioned queries"',
			[
				new CodeSample(
					<<<'CODE_BEFORE'
$query = $builder->select('id')
    ->from('mounts', 'm')
    ->innerJoin('m', 'filecache', 'f', $builder->expr()->eq('m.root_id', 'f.fileid'))
    ->where($builder->expr()->eq('user_id', $builder->createNamedParameter($uid)))
    ->setMaxResults(1)
    ->setFirstResult(0);
CODE_BEFORE
					,
					<<<'CODE_AFTER'
$query = $builder->select('id')
    ->from('mounts', 'm')
    ->innerJoin('m', 'filecache', 'f', $builder->expr()->eq('m.root_id', 'f.fileid'))
    ->where($builder->expr()->eq('user_id', $builder->createNamedParameter($uid)));
CODE_AFTER
				),
			]
		);
	}

	/**
	 * @return array<class-string<Node>>
	 */
	public function getNodeTypes(): array {
		return [MethodCall::class];
	}

	/**
	 * @param MethodCall $node
	 */
	public function refactor(Node $node): ?Node {
		if (!($node instanceof MethodCall)) {
			return null;
		}

		// Only flag setMaxResults() or setFirstResult() calls
		$isLimitMethod = false;
		foreach (self::LIMIT_METHODS as $method) {
			if ($this->isName($node->name, $method)) {
				$isLimitMethod = true;
				break;
			}
		}

		if (!$isLimitMethod) {
			return null;
		}

		// Walk the fluent chain on the receiver side to find innerJoin or leftJoin
		if (!$this->chainContainsJoin($node->var)) {
			return null;
		}

		// Replace `$chain->setMaxResults(...)->setFirstResult(...)` with just `$chain`.
		return $node->var;
	}

	private function chainContainsJoin(Expr $expr): bool {
		if (!($expr instanceof MethodCall)) {
			return false;
		}
		foreach (self::JOIN_METHODS as $joinMethod) {
			if ($this->isName($expr->name, $joinMethod)) {
				// Verify the join has a condition (4th parameter)
				// innerJoin($fromAlias, $join, $alias, $condition, ...)
				$args = $expr->getArgs();
				if (count($args) >= 4) {
					return true;
				}
			}
		}
		return $this->chainContainsJoin($expr->var);
	}
}

