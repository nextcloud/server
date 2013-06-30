<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Connector\Sabre;

use OC\Files\Filesystem;

class ObjectTree extends \Sabre_DAV_ObjectTree {
	/**
	 * Returns the INode object for the requested path
	 *
	 * @param string $path
	 * @throws \Sabre_DAV_Exception_NotFound
	 * @return \Sabre_DAV_INode
	 */
	public function getNodeForPath($path) {

		$path = trim($path, '/');
		if (isset($this->cache[$path])) return $this->cache[$path];

		// Is it the root node?
		if (!strlen($path)) {
			return $this->rootNode;
		}

		$info = Filesystem::getFileInfo($path);

		if (!$info) {
			throw new \Sabre_DAV_Exception_NotFound('File with name ' . $path . ' could not be located');
		}

		if ($info['mimetype'] == 'httpd/unix-directory') {
			$node = new \OC_Connector_Sabre_Directory($path);
		} else {
			$node = new \OC_Connector_Sabre_File($path);
		}

		$node->setFileinfoCache($info);

		$this->cache[$path] = $node;
		return $node;

	}
}
