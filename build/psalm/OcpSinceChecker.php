<?php

declare(strict_types=1);

/**
 * @copyright 2023 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author 2023 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
			self::checkMethodComment($method, $statementsSource);
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
	}

	private static function checkMethodComment(Stmt $stmt, FileSource $statementsSource): void {
		$docblock = $stmt->getDocComment();

		if ($docblock === null) {
			IssueBuffer::maybeAdd(
				new InvalidDocblock(
					'PHPDoc is required for methods in OCP.',
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
					'@since is required for methods in OCP.',
					new CodeLocation($statementsSource, $stmt)
				)
			);
		}
	}
}
