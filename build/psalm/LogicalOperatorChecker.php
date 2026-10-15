<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Psalm\CodeLocation;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

class LogicalOperatorChecker implements AfterExpressionAnalysisInterface {
	public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool {
		$stmt = $event->getExpr();
		if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
			|| $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr) {
			IssueBuffer::maybeAdd(
				new \Psalm\Issue\UnrecognizedExpression(
					'Logical binary operators AND and OR are not allowed in the Nextcloud codebase',
					new CodeLocation($event->getStatementsSource()->getSource(), $stmt),
				),
				$event->getStatementsSource()->getSuppressedIssues(),
			);
		}
		return null;
	}
}
