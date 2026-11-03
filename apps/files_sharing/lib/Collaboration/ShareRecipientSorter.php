<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Collaboration;

use OCP\Collaboration\AutoComplete\ISorter;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IUserSession;
use OCP\Share\IManager;

class ShareRecipientSorter implements ISorter {

	public function __construct(
		private IManager $shareManager,
		private IRootFolder $rootFolder,
		private IUserSession $userSession,
	) {
	}

	#[\Override]
	public function getId(): string {
		return 'share-recipients';
	}

	#[\Override]
	public function sort(array &$sortArray, array $context): void {
		// let's be tolerant. Comments  uses "files" by default, other usages are often singular
		if ($context['itemType'] !== 'files' && $context['itemType'] !== 'file') {
			return;
		}
		$user = $this->userSession->getUser();
		if ($user === null) {
			return;
		}
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		/** @var Node[] $nodes */
		$node = $userFolder->getFirstNodeById((int)$context['itemId']);
		if (!$node) {
			return;
		}
		$al = $this->shareManager->getAccessList($node);

		foreach ($sortArray as $type => &$byType) {
			if (!isset($al[$type]) || !is_array($al[$type])) {
				continue;
			}

			usort($byType, function ($a, $b) use ($al, $type) {
				return $this->compare($a, $b, $al[$type]);
			});
		}
	}

	protected function compare(array $a, array $b, array $al): int {
		$a = $a['value']['shareWith'];
		$b = $b['value']['shareWith'];

		$valueA = (int)in_array($a, $al, true);
		$valueB = (int)in_array($b, $al, true);

		return $valueB - $valueA;
	}
}
