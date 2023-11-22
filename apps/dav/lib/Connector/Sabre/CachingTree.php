<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCA\DAV\Connector\Sabre;

use Sabre\DAV\Tree;

class CachingTree extends Tree {
	/**
	 * Store a node in the cache
	 */
	public function cacheNode(Node $node, ?string $path = null): void {
		if (is_null($path)) {
			$path = $node->getPath();
		}
		$this->cache[trim($path, '/')] = $node;
	}

	/**
	 * @param string $path
	 * @return void
	 */
	public function markDirty($path) {
		// We don't care enough about sub-paths
		// flushing the entire cache
		$path = trim($path, '/');
		foreach ($this->cache as $nodePath => $node) {
			$nodePath = (string) $nodePath;
			if ('' === $path || $nodePath == $path || str_starts_with($nodePath, $path . '/')) {
				unset($this->cache[$nodePath]);
			}
		}
	}
}
