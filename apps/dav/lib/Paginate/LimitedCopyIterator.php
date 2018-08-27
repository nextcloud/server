<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
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

namespace OCA\DAV\Paginate;

/**
 * Save a copy of the first X items into a separate iterator
 *
 * this allows us to pass the iterator to the cache while keeping a copy
 * of the first X items
 */
class LimitedCopyIterator extends \AppendIterator {
	/** @var array */
	private $copy;

	public function __construct(\Traversable $iterator, int $count) {
		parent::__construct();

		if (!$iterator instanceof \Iterator) {
			$iterator = new \IteratorIterator($iterator);
		}
		$iterator = new \NoRewindIterator($iterator);

		while($iterator->valid() && count($this->copy) < $count) {
			$this->copy[] = $iterator->current();
			$iterator->next();
		}

		$this->append($this->getFirstItems());
		$this->append($iterator);
	}

	public function getFirstItems(): \Iterator {
		return new \ArrayIterator($this->copy);
	}
}