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
		$stmt = $event->getStmt();
		$statementsSource = $event->getStatementsSource();

		self::checkClassComment($stmt, $statementsSource);

		foreach ($stmt->getMethods() as $method) {
			self::checkMethodOrConstantComment($method, $statementsSource, 'method');
		}

		foreach ($stmt->getConstants() as $constant) {
			self::checkMethodOrConstantComment($constant, $statementsSource, 'constant');
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

	private static function checkMethodOrConstantComment(Stmt $stmt, FileSource $statementsSource, string $type): void {
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
}
