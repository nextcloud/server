<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Files\Search;

use OCP\Files\FileInfo;
use OCP\Files\Search\ISearchOrder;

class SearchOrder implements ISearchOrder {
	/** @var  string */
	private $direction;
	/** @var  string */
	private $field;

	/**
	 * SearchOrder constructor.
	 *
	 * @param string $direction
	 * @param string $field
	 */
	public function __construct($direction, $field) {
		$this->direction = $direction;
		$this->field = $field;
	}

	/**
	 * @return string
	 */
	public function getDirection() {
		return $this->direction;
	}

	/**
	 * @return string
	 */
	public function getField() {
		return $this->field;
	}

	public function sortFileInfo(FileInfo $a, FileInfo $b): int {
		$cmp = $this->sortFileInfoNoDirection($a, $b);
		return $cmp * ($this->direction === ISearchOrder::DIRECTION_ASCENDING ? 1 : -1);
	}

	private function sortFileInfoNoDirection(FileInfo $a, FileInfo $b): int {
		switch ($this->field) {
			case 'name':
				return $a->getName() <=> $b->getName();
			case 'mimetype':
				return $a->getMimetype() <=> $b->getMimetype();
			case 'mtime':
				return $a->getMtime() <=> $b->getMtime();
			case 'size':
				return $a->getSize() <=> $b->getSize();
			case 'fileid':
				return $a->getId() <=> $b->getId();
			case 'permissions':
				return $a->getPermissions() <=> $b->getPermissions();
			default:
				return 0;
		}
	}
}
