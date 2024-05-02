<?php
/**
 * ownCloud
 *
 * @author Michael Gapczynski
 * @copyright 2012 Michael Gapczynski mtgap@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Test\Share;

use OC\Share20\Manager;
use OCP\Server;
use OCP\Share\IShare;

class Backend implements \OCP\Share_Backend {
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
			$target = "test.txt";
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
			while (in_array($name.$append.$ext, $knownTargets)) {
				$append = $i;
				$i++;
			}
			$target = $name.$append.$ext;
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
