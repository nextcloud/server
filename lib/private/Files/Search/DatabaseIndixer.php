<?php
/**
 * @author Georg Ehrke <georg@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
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

namespace OCA\Files\Search;

use OCP\Files\Search\IIndexer;

class DatabaseIndexer implements IIndexer {

	/**
	 * @var \OCP\Files\Storage\IStorage[]
	 */
	protected $storages;

	/**
	 * @param \OCP\Files\Storage\IStorage[] $storages
	 */
	public function setStorages($storages) {
		$this->storages = $storages;
	}

	/**
	 * @param $query
	 * @param null|integer $page
	 * @param null|integer $size
	 * @return \OCP\Search\ScoredResult[]
	 */
	public function search($query, $page=null, $size=null) {
		return [];
	}
	
}
