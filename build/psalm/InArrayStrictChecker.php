<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use Psalm\CodeLocation;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

/**
 * Complains about in_array() calls that do not explicitly set the $strict parameter
 */
class InArrayStrictChecker implements AfterExpressionAnalysisInterface {
	public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool {
		$stmt = $event->getExpr();
		if (!$stmt instanceof FuncCall
			|| !$stmt->name instanceof Name
			|| strtolower($stmt->name->toString()) !== 'in_array') {
			return null;
		}

		$hasStrictArg = false;
		foreach ($stmt->getArgs() as $index => $arg) {
			if ($arg->name !== null) {
				if ($arg->name->toString() === 'strict') {
					$hasStrictArg = true;
					break;
				}
				continue;
			}
			if ($index === 2) {
				$hasStrictArg = true;
				break;
			}
		}

		if (!$hasStrictArg) {
			IssueBuffer::maybeAdd(
				new \Psalm\Issue\UnrecognizedExpression(
					'in_array() must be called with an explicit $strict parameter',
					new CodeLocation($event->getStatementsSource()->getSource(), $stmt),
				),
				$event->getStatementsSource()->getSuppressedIssues(),
			);
		}

		return null;
	}
}
