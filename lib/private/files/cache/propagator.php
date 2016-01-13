<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
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

namespace OC\Files\Cache;

/**
 * Propagate etags and mtimes within the storage
 */
class Propagator {
	/**
	 * @var \OC\Files\Storage\Storage
	 */
	protected $storage;

	/**
	 * @param \OC\Files\Storage\Storage $storage
	 */
	public function __construct(\OC\Files\Storage\Storage $storage) {
		$this->storage = $storage;
	}


	/**
	 * @param string $internalPath
	 * @param int $time
	 * @return array[] all propagated entries
	 */
	public function propagateChange($internalPath, $time) {
		$cache = $this->storage->getCache($internalPath);

		$parentId = $cache->getParentId($internalPath);
		$propagatedEntries = [];
		while ($parentId !== -1) {
			$entry = $cache->get($parentId);
			$propagatedEntries[] = $entry;
			if (!$entry) {
				return $propagatedEntries;
			}
			$mtime = max($time, $entry['mtime']);

			$cache->update($parentId, ['mtime' => $mtime, 'etag' => $this->storage->getETag($entry['path'])]);

			$parentId = $entry['parent'];
		}

		return $propagatedEntries;
	}
}
