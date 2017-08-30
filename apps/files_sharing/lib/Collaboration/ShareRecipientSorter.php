<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_Sharing\Collaboration;


use OCP\Collaboration\AutoComplete\ISorter;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Share\IManager;

class ShareRecipientSorter implements ISorter {

	/** @var IManager */
	private $shareManager;
	/** @var Folder */
	private $userFolder;

	public function __construct(IManager $shareManager, Folder $userFolder) {
		$this->shareManager = $shareManager;
		$this->userFolder = $userFolder;
	}

	public function getId() {
		return 'share-recipients';
	}

	public function sort(array &$sortArray, array $context) {
		// let's be tolerant. Comments  uses "files" by default, other usages are often singular
		if($context['itemType'] !== 'files' && $context['itemType'] !== 'file') {
			return;
		}
		/** @var Node[] $nodes */
		$nodes = $this->userFolder->getById((int)$context['itemId']);
		if(count($nodes) === 0) {
			return;
		}
		$al = $this->shareManager->getAccessList($nodes[0]);

		foreach ($sortArray as $type => &$byType) {
			if(!isset($al[$type]) || !is_array($al[$type])) {
				continue;
			}
			usort($byType, function ($a, $b) use ($al, $type) {
				return  $this->compare($a, $b, $al[$type]);
			});
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
