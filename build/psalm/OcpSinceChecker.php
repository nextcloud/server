<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use Psalm\CodeLocation;
use Psalm\DocComment;
use Psalm\Exception\DocblockParseException;
use Psalm\FileSource;
use Psalm\Issue\InvalidDocblock;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;

class OcpSinceChecker implements Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface {
	public static function afterClassLikeVisit(AfterClassLikeVisitEvent $event): void {
		$classLike = $event->getStmt();
		$statementsSource = $event->getStatementsSource();

		if (!str_contains($statementsSource->getFilePath(), '/lib/public/')) {
			return;
		}

		$isTesting = str_contains($statementsSource->getFilePath(), '/lib/public/Notification/')
			|| str_contains($statementsSource->getFilePath(), '/lib/public/Config/')
			|| str_contains($statementsSource->getFilePath(), '/lib/public/Migration/Attributes/')
			|| str_contains($statementsSource->getFilePath(), 'CalendarEventStatus');

		if ($isTesting) {
			self::checkStatementAttributes($classLike, $statementsSource);
		} else {
			self::checkClassComment($classLike, $statementsSource);
		}

		foreach ($classLike->stmts as $stmt) {
			if ($stmt instanceof ClassConst) {
				self::checkStatementComment($stmt, $statementsSource, 'constant');
			}

			if ($stmt instanceof ClassMethod) {
				self::checkStatementComment($stmt, $statementsSource, 'method');
			}

			if ($stmt instanceof EnumCase) {
				if ($isTesting) {
					self::checkStatementAttributes($classLike, $statementsSource);
				} else {
					self::checkStatementComment($stmt, $statementsSource, 'enum');
				}
			}
		}
	}

	private static function checkStatementAttributes(ClassLike $stmt, FileSource $statementsSource): void {
		$hasAppFrameworkAttribute = false;
		$mustBeConsumable = false;
		$isConsumable = false;
		foreach ($stmt->attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				if (in_array($attr->name->getLast(), [
					'Catchable',
					'Consumable',
					'Dispatchable',
					'Implementable',
					'Listenable',
					'Throwable',
				], true)) {
					$hasAppFrameworkAttribute = true;
					self::checkAttributeHasValidSinceVersion($attr, $statementsSource);
				}
				if (in_array($attr->name->getLast(), [
					'Catchable',
					'Consumable',
					'Listenable',
				], true)) {
					$isConsumable = true;
				}
				if ($attr->name->getLast() === 'ExceptionalImplementable') {
					$mustBeConsumable = true;
				}
			}
		}

		if ($mustBeConsumable && !$isConsumable) {
			IssueBuffer::maybeAdd(
				new InvalidDocblock(
					'Attribute OCP\\AppFramework\\Attribute\\ExceptionalImplementable is only valid on classes that also have OCP\\AppFramework\\Attribute\\Consumable',
					new CodeLocation($statementsSource, $stmt)
				)
			);
		}

		if (!$hasAppFrameworkAttribute) {
			IssueBuffer::maybeAdd(
				new InvalidDocblock(
					'At least one of the OCP\\AppFramework\\Attribute attributes is required',
					new CodeLocation($statementsSource, $stmt)
				)
			);
		}
	}

	private static function checkClassComment(ClassLike $stmt, FileSource $statementsSource): void {
		$docblock = $stmt->getDocComment();

		if ($docblock === null) {
			IssueBuffer::maybeAdd(
				new InvalidDocblock(
					'PHPDoc is required for classes/interfaces in OCP.',
					new CodeLocation($statementsSource, $stmt)
				)
			);
			return;
		}

		try {
			$parsedDocblock = DocComment::parsePreservingLength($docblock);
		} catch (DocblockParseException $e) {
			IssueBuffer::maybeAdd(
				new InvalidDocblock(
					$e->getMessage(),
					new CodeLocation($statementsSource, $stmt)
				)
			);
			return;
		}

		if (!isset($parsedDocblock->tags['since'])) {
			IssueBuffer::maybeAdd(
				new InvalidDocblock(
					'@since is required for classes/interfaces in OCP.',
					new CodeLocation($statementsSource, $stmt)
				)
			);
		}

		if (isset($parsedDocblock->tags['depreacted'])) {
			IssueBuffer::maybeAdd(
				new InvalidDocblock(
					'Typo in @deprecated for classes/interfaces in OCP.',
					new CodeLocation($statementsSource, $stmt)
				)
			);
		}
	}

	private static function checkStatementComment(Stmt $stmt, FileSource $statementsSource, string $type): void {
		$docblock = $stmt->getDocComment();

		if ($docblock === null) {
			IssueBuffer::maybeAdd(
				new InvalidDocblock(
					'PHPDoc is required for ' . $type . 's in OCP.',
					new CodeLocation($statementsSource, $stmt)
				),
			);
			return;
		}

		try {
			$parsedDocblock = DocComment::parsePreservingLength($docblock);
		} catch (DocblockParseException $e) {
			IssueBuffer::maybeAdd(
				new InvalidDocblock(
					$e->getMessage(),
					new CodeLocation($statementsSource, $stmt)
				)
			);
			return;
		}

		if (!isset($parsedDocblock->tags['since'])) {
			IssueBuffer::maybeAdd(
				new InvalidDocblock(
					'@since is required for ' . $type . 's in OCP.',
					new CodeLocation($statementsSource, $stmt)
				)
			);
		}

		if (isset($parsedDocblock->tags['depreacted'])) {
			IssueBuffer::maybeAdd(
				new InvalidDocblock(
					'Typo in @deprecated for ' . $type . ' in OCP.',
					new CodeLocation($statementsSource, $stmt)
				)
			);
		}
	}

	private static function checkAttributeHasValidSinceVersion(\PhpParser\Node\Attribute $stmt, FileSource $statementsSource): void {
		foreach ($stmt->args as $arg) {
			if ($arg->name?->name === 'since') {
				if (!$arg->value instanceof \PhpParser\Node\Scalar\String_) {
					IssueBuffer::maybeAdd(
						new InvalidDocblock(
							'Attribute since argument is not a valid version string',
							new CodeLocation($statementsSource, $stmt)
						)
					);
				} else {
					if (!preg_match('/^[1-9][0-9]*(\.[0-9]+){0,3}$/', $arg->value->value)) {
						IssueBuffer::maybeAdd(
							new InvalidDocblock(
								'Attribute since argument is not a valid version string',
								new CodeLocation($statementsSource, $stmt)
							)
						);
					}
				}
			}
		}
	}
}
