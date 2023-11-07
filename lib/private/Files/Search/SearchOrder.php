<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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
	public function __construct(
		private string $direction,
		private string $field,
		private string $extra = ''
	) {
	}

	/**
	 * @return string
	 */
	public function getDirection(): string {
		return $this->direction;
	}

	/**
	 * @return string
	 */
	public function getField(): string {
		return $this->field;
	}

	/**
	 * @return string
	 * @since 28.0.0
	 */
	public function getExtra(): string {
		return $this->extra;
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
