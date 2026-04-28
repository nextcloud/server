<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Static_;
use Psalm\CodeLocation;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterStatementAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterStatementAnalysisEvent;

/**
 * Complains about static property in classes and static vars in methods
 */
class StaticVarsChecker implements AfterStatementAnalysisInterface {
	public static function afterStatementAnalysis(AfterStatementAnalysisEvent $event): ?bool {
		$stmt = $event->getStmt();
		if ($stmt instanceof Property) {
			if ($stmt->isStatic()) {
				IssueBuffer::maybeAdd(
					// ImpureStaticProperty is close enough, all static properties are impure to my eyes
					new \Psalm\Issue\ImpureStaticProperty(
						'Static property should not be used as they do not follow requests lifecycle',
						new CodeLocation($event->getStatementsSource(), $stmt),
					),
					$event->getStatementsSource()->getSuppressedIssues(),
				);
			}
		} elseif ($stmt instanceof Static_) {
			IssueBuffer::maybeAdd(
				// Same logic
				new \Psalm\Issue\ImpureStaticVariable(
					'Static var should not be used as they do not follow requests lifecycle and are hard to reset',
					new CodeLocation($event->getStatementsSource(), $stmt),
				),
				$event->getStatementsSource()->getSuppressedIssues(),
			);
		}
		return null;
	}
}
