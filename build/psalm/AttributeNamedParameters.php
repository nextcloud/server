<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use PhpParser\Node\Attribute;
use Psalm\CodeLocation;
use Psalm\FileSource;
use Psalm\Issue\InvalidDocblock;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;

class AttributeNamedParameters implements Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface {
	public static function afterClassLikeVisit(AfterClassLikeVisitEvent $event): void {
		$stmt = $event->getStmt();
		$statementsSource = $event->getStatementsSource();

		foreach ($stmt->attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				self::checkAttribute($attr, $statementsSource);
			}
		}

		foreach ($stmt->getMethods() as $method) {
			foreach ($method->attrGroups as $attrGroup) {
				foreach ($attrGroup->attrs as $attr) {
					self::checkAttribute($attr, $statementsSource);
				}
			}
		}
	}

	private static function checkAttribute(Attribute $stmt, FileSource $statementsSource): void {
		if ($stmt->name->getLast() === 'Attribute') {
			return;
		}

		foreach ($stmt->args as $arg) {
			if ($arg->name === null) {
				IssueBuffer::maybeAdd(
					new InvalidDocblock(
						'Attribute arguments must be named.',
						new CodeLocation($statementsSource, $stmt)
					)
				);
			}
		}
	}
}
