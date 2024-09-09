<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use Psalm\CodeLocation;
use Psalm\Issue\InvalidDocblock;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;

include __DIR__ . '/../../3rdparty/autoload.php';

class OcInheritDoc implements Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface {
	public static function afterClassLikeVisit(AfterClassLikeVisitEvent $event): void {
		$childClass = $event->getStmt();
		$statementsSource = $event->getStatementsSource();

		if (!$childClass instanceof Class_) {
			return;
		}

		$childClassName = $childClass->getAttribute('namespacedName')?->toString() ?? '';
		if (!str_starts_with($childClassName, 'OC\\')) {
			return;
		}

		/** @var Name $impl */
		foreach ($childClass->implements as $impl) {
			$parentClassName = $impl->getAttribute('resolvedName') ?? '';
			if ($parentClassName === 'OCP\\Capabilities\\ICapability' || $parentClassName === 'OCP\\Capabilities\\IPublicCapability' || !str_starts_with($parentClassName, 'OCP\\')) {
				continue;
			}

			$parentClass = new ReflectionClass($parentClassName);
			$parentMethods = $parentClass->getMethods();

			foreach ($childClass->getMethods() as $childMethod) {
				foreach ($parentMethods as $parentMethod) {
					if (strtolower($childMethod->name->name) !== strtolower($parentMethod->name)) {
						continue;
					}

					$doc = $childMethod->getDocComment();
					if ($doc !== null) {
						IssueBuffer::maybeAdd(
							new InvalidDocblock(
								'Docblock for OCP implementation must be empty to inherit the interface.',
								new CodeLocation($statementsSource, $childMethod)
							)
						);
					}

					break;
				}
			}
		}
	}
}
