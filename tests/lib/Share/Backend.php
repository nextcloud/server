<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Share;

use OC\Share20\Manager;
use OCP\Server;
use OCP\Share\IShare;
use OCP\Share_Backend;

class Backend implements Share_Backend {
	public const FORMAT_SOURCE = 0;
	public const FORMAT_TARGET = 1;
	public const FORMAT_PERMISSIONS = 2;

	private $testItem1 = 'test.txt';
	private $testItem2 = 'share.txt';
	private $testId = 1;

	public function isValidSource($itemSource, $uidOwner) {
		if ($itemSource == $this->testItem1 || $itemSource == $this->testItem2 || $itemSource == 1) {
			return true;
		}
	}

	public function generateTarget($itemSource, $shareWith, $exclude = null) {
		// Always make target be test.txt to cause conflicts

		if (substr($itemSource, 0, strlen('test')) !== 'test') {
			$target = 'test.txt';
		} else {
			$target = $itemSource;
		}


		$shareManager = Server::get(Manager::class);
		$shares = array_merge(
			$shareManager->getSharedWith($shareWith, IShare::TYPE_USER),
			$shareManager->getSharedWith($shareWith, IShare::TYPE_GROUP),
		);

		$knownTargets = [];
		foreach ($shares as $share) {
			$knownTargets[] = $share['item_target'];
		}


		if (in_array($target, $knownTargets)) {
			$pos = strrpos($target, '.');
			$name = substr($target, 0, $pos);
			$ext = substr($target, $pos);
			$append = '';
			$i = 1;
			while (in_array($name . $append . $ext, $knownTargets)) {
				$append = $i;
				$i++;
			}
			$target = $name . $append . $ext;
		}

		return $target;
	}

	public function formatItems($items, $format, $parameters = null) {
		$testItems = [];
		foreach ($items as $item) {
			if ($format === self::FORMAT_SOURCE) {
				$testItems[] = $item['item_source'];
			} elseif ($format === self::FORMAT_TARGET) {
				$testItems[] = $item['item_target'];
			} elseif ($format === self::FORMAT_PERMISSIONS) {
				$testItems[] = $item['permissions'];
			}
		}
		return $testItems;
	}

	public function isShareTypeAllowed($shareType) {
		return true;
	}
}
