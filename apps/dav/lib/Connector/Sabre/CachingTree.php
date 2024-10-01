<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
			$nodePath = (string)$nodePath;
			if ($path === '' || $nodePath == $path || str_starts_with($nodePath, $path . '/')) {
				unset($this->cache[$nodePath]);
			}
		}
	}
}
