<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Comments\Collaboration;

use OCP\Collaboration\AutoComplete\ISorter;
use OCP\Comments\ICommentsManager;

class CommentersSorter implements ISorter {
	public function __construct(
		private ICommentsManager $commentsManager,
	) {
	}

	#[\Override]
	public function getId(): string {
		return 'commenters';
	}

	/**
	 * Sorts people who commented on the given item ahead of others, in
	 * descending order
	 */
	#[\Override]
	public function sort(array &$sortArray, array $context): void {
		if (!isset($context['itemType'], $context['itemId'])) {
			return;
		}

		$commenters = $this->retrieveCommentsInformation($context['itemType'], $context['itemId']);
		if ($commenters === []) {
			return;
		}

		foreach ($sortArray as $type => &$byType) {
			if (!isset($commenters[$type])) {
				continue;
			}

			usort($byType, function ($a, $b) use ($commenters, $type) {
				return $this->compare($a, $b, $commenters[$type]);
			});
		}
	}

	/**
	 * @return array<string, array<string, int>>
	 */
	protected function retrieveCommentsInformation(string $type, string $id): array {
		$comments = $this->commentsManager->getForObject($type, $id);
		if (count($comments) === 0) {
			return [];
		}

		$actors = [];
		foreach ($comments as $comment) {
			if (!isset($actors[$comment->getActorType()])) {
				$actors[$comment->getActorType()] = [];
			}
			if (!isset($actors[$comment->getActorType()][$comment->getActorId()])) {
				$actors[$comment->getActorType()][$comment->getActorId()] = 1;
			} else {
				$actors[$comment->getActorType()][$comment->getActorId()]++;
			}
		}
		return $actors;
	}

	protected function compare(array $a, array $b, array $commenters): int {
		$a = $a['value']['shareWith'];
		$b = $b['value']['shareWith'];

		$valueA = $commenters[$a] ?? 0;
		$valueB = $commenters[$b] ?? 0;

		return $valueB - $valueA;
	}
}
