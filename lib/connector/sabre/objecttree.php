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

		if ($info['mimetype'] === 'httpd/unix-directory') {
			$node = new \OC_Connector_Sabre_Directory($path);
		} else {
			$node = new \OC_Connector_Sabre_File($path);
		}

		$node->setFileinfoCache($info);

		$this->cache[$path] = $node;
		return $node;

	}

	/**
	 * Moves a file from one location to another
	 *
	 * @param string $sourcePath The path to the file which should be moved
	 * @param string $destinationPath The full destination path, so not just the destination parent node
	 * @throws \Sabre_DAV_Exception_Forbidden
	 * @return int
	 */
	public function move($sourcePath, $destinationPath) {

		$sourceNode = $this->getNodeForPath($sourcePath);
		if ($sourceNode instanceof \Sabre_DAV_ICollection and $this->nodeExists($destinationPath)) {
			throw new \Sabre_DAV_Exception_Forbidden('Could not copy directory ' . $sourceNode . ', target exists');
		}
		list($sourceDir,) = \Sabre_DAV_URLUtil::splitPath($sourcePath);
		list($destinationDir,) = \Sabre_DAV_URLUtil::splitPath($destinationPath);

		Filesystem::rename($sourcePath, $destinationPath);

		$this->markDirty($sourceDir);
		$this->markDirty($destinationDir);

	}

	/**
	 * Copies a file or directory.
	 *
	 * This method must work recursively and delete the destination
	 * if it exists
	 *
	 * @param string $source
	 * @param string $destination
	 * @return void
	 */
	public function copy($source, $destination) {

		if (Filesystem::is_file($source)) {
			Filesystem::copy($source, $destination);
		} else {
			Filesystem::mkdir($destination);
			$dh = Filesystem::opendir($source);
			while (($subnode = readdir($dh)) !== false) {

				if ($subnode == '.' || $subnode == '..') continue;
				$this->copy($source . '/' . $subnode, $destination . '/' . $subnode);

			}
		}

		list($destinationDir,) = \Sabre_DAV_URLUtil::splitPath($destination);
		$this->markDirty($destinationDir);
	}
}
