<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: MIT
 */
use Psalm\CodeLocation;
use Psalm\Plugin\EventHandler\AfterFunctionLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterFunctionLikeAnalysisEvent;
use Psalm\Type\TaintKindGroup;

class AppFrameworkTainter implements AfterFunctionLikeAnalysisInterface {
	public static function afterStatementAnalysis(AfterFunctionLikeAnalysisEvent $event): ?bool {
		if ($event->getStatementsSource()->getFQCLN() === null) {
			return null;
		}
		if (!$event->getCodebase()->classExtendsOrImplements($event->getStatementsSource()->getFQCLN(), \OCP\AppFramework\Controller::class)) {
			return null;
		}
		if (!($event->getStmt() instanceof PhpParser\Node\Stmt\ClassMethod)) {
			return null;
		}
		if (!$event->getStmt()->isPublic() || $event->getStmt()->isMagic()) {
			return null;
		}
		foreach ($event->getStmt()->params as $i => $param) {
			$expr_type = new Psalm\Type\Union([new Psalm\Type\Atomic\TString()]);
			$expr_identifier = (strtolower($event->getStatementsSource()->getFQCLN()) . '::' . strtolower($event->getFunctionlikeStorage()->cased_name) . '#' . ($i + 1));
			$event->getCodebase()->addTaintSource(
				$expr_type,
				$expr_identifier,
				TaintKindGroup::ALL_INPUT,
				new CodeLocation($event->getStatementsSource(), $param)
			);
		}

		return null;
	}
}
