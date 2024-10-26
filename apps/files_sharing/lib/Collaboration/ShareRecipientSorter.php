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

	public function getId(): string {
		return 'share-recipients';
	}

	public function sort(array &$sortArray, array $context) {
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

			// at least on PHP 5.6 usort turned out to be not stable. So we add
			// the current index to the value and compare it on a draw
			$i = 0;
			$workArray = array_map(function ($element) use (&$i) {
				return [$i++, $element];
			}, $byType);

			usort($workArray, function ($a, $b) use ($al, $type) {
				$result = $this->compare($a[1], $b[1], $al[$type]);
				if ($result === 0) {
					$result = $a[0] - $b[0];
				}
				return $result;
			});

			// and remove the index values again
			$byType = array_column($workArray, 1);
		}
	}

	/**
	 * @param array $a
	 * @param array $b
	 * @param array $al
	 * @return int
	 */
	protected function compare(array $a, array $b, array $al) {
		$a = $a['value']['shareWith'];
		$b = $b['value']['shareWith'];

		$valueA = (int)in_array($a, $al, true);
		$valueB = (int)in_array($b, $al, true);

		return $valueB - $valueA;
	}
}
