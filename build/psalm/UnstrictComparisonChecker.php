<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Psalm\CodeLocation;
use Psalm\IssueBuffer;
use Psalm\Node\VirtualNode;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TNamedObject;

class UnstrictComparisonChecker implements AfterExpressionAnalysisInterface {
	/**
	 * Classes that must be compared by value (==) since strict comparison (===)
	 * would compare instance identity instead, which is almost never what is intended.
	 */
	private const VALUE_COMPARISON_CLASSES = [
		\DateTimeInterface::class,
		\DateTimeZone::class,
	];

	public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool {
		$stmt = $event->getExpr();
		if ($stmt instanceof VirtualNode) {
			// Synthesized by Psalm itself (e.g. the implicit == of a switch/case), not written in the source
			return null;
		}
		if (!$stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
			&& !$stmt instanceof PhpParser\Node\Expr\BinaryOp\NotEqual) {
			return null;
		}

		if (self::isValueComparisonType($event, $stmt->left) && self::isValueComparisonType($event, $stmt->right)) {
			return null;
		}

		IssueBuffer::maybeAdd(
			new \Psalm\Issue\UnrecognizedExpression(
				'Non-strict comparison operators == and != are not allowed in the Nextcloud codebase, use === and !== instead',
				new CodeLocation($event->getStatementsSource()->getSource(), $stmt),
			),
			$event->getStatementsSource()->getSuppressedIssues(),
		);
		return null;
	}

	private static function isValueComparisonType(AfterExpressionAnalysisEvent $event, PhpParser\Node\Expr $expr): bool {
		$type = $event->getStatementsSource()->getNodeTypeProvider()->getType($expr);
		$atomicTypes = $type?->getAtomicTypes() ?? [];
		if ($atomicTypes === []) {
			return false;
		}

		foreach ($atomicTypes as $atomic) {
			if (!$atomic instanceof TNamedObject) {
				return false;
			}

			$isAllowed = false;
			foreach (self::VALUE_COMPARISON_CLASSES as $allowedClass) {
				if (is_a($atomic->value, $allowedClass, true)) {
					$isAllowed = true;
					break;
				}
			}
			if (!$isAllowed) {
				return false;
			}
		}

		return true;
	}
}
