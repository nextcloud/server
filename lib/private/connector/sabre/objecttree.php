<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Connector\Sabre;

use OC\Files\FileInfo;
use OC\Files\Filesystem;

class ObjectTree extends \Sabre_DAV_ObjectTree {

	/**
	 * @var \OC\Files\View
	 */
	protected $fileView;

	/**
	 * Creates the object
	 *
	 * This method expects the rootObject to be passed as a parameter
	 */
	public function __construct() {
	}

	/**
	 * @param \Sabre_DAV_ICollection $rootNode
	 * @param \OC\Files\View $view
	 */
	public function init(\Sabre_DAV_ICollection $rootNode, \OC\Files\View $view) {
		$this->rootNode = $rootNode;
		$this->fileView = $view;
	}

	/**
	 * Returns the INode object for the requested path
	 *
	 * @param string $path
	 * @throws \Sabre_DAV_Exception_ServiceUnavailable
	 * @throws \Sabre_DAV_Exception_NotFound
	 * @return \Sabre_DAV_INode
	 */
	public function getNodeForPath($path) {
		if (!$this->fileView) {
			throw new \Sabre_DAV_Exception_ServiceUnavailable('filesystem not setup');
		}

		$path = trim($path, '/');
		if (isset($this->cache[$path])) {
			return $this->cache[$path];
		}

		// Is it the root node?
		if (!strlen($path)) {
			return $this->rootNode;
		}

		if (pathinfo($path, PATHINFO_EXTENSION) === 'part') {
			// read from storage
			$absPath = $this->fileView->getAbsolutePath($path);
			list($storage, $internalPath) = Filesystem::resolvePath('/' . $absPath);
			if ($storage) {
				/**
				 * @var \OC\Files\Storage\Storage $storage
				 */
				$scanner = $storage->getScanner($internalPath);
				// get data directly
				$data = $scanner->getData($internalPath);
				$info = new FileInfo($absPath, $storage, $internalPath, $data);
			} else {
				$info = null;
			}
		} else {
			// read from cache
			$info = $this->fileView->getFileInfo($path);
		}

		if (!$info) {
			throw new \Sabre_DAV_Exception_NotFound('File with name ' . $path . ' could not be located');
		}

		if ($info->getType() === 'dir') {
			$node = new \OC_Connector_Sabre_Directory($this->fileView, $info);
		} else {
			$node = new \OC_Connector_Sabre_File($this->fileView, $info);
		}

		$this->cache[$path] = $node;
		return $node;

	}

	/**
	 * Moves a file from one location to another
	 *
	 * @param string $sourcePath The path to the file which should be moved
	 * @param string $destinationPath The full destination path, so not just the destination parent node
	 * @throws \Sabre_DAV_Exception_BadRequest
	 * @throws \Sabre_DAV_Exception_ServiceUnavailable
	 * @throws \Sabre_DAV_Exception_Forbidden
	 * @return int
	 */
	public function move($sourcePath, $destinationPath) {
		if (!$this->fileView) {
			throw new \Sabre_DAV_Exception_ServiceUnavailable('filesystem not setup');
		}

		$sourceNode = $this->getNodeForPath($sourcePath);
		if ($sourceNode instanceof \Sabre_DAV_ICollection and $this->nodeExists($destinationPath)) {
			throw new \Sabre_DAV_Exception_Forbidden('Could not copy directory ' . $sourceNode . ', target exists');
		}
		list($sourceDir,) = \Sabre_DAV_URLUtil::splitPath($sourcePath);
		list($destinationDir,) = \Sabre_DAV_URLUtil::splitPath($destinationPath);

		// check update privileges
		if (!$this->fileView->isUpdatable($sourcePath)) {
			throw new \Sabre_DAV_Exception_Forbidden();
		}
		if ($sourceDir !== $destinationDir) {
			// for a full move we need update privileges on sourcePath and sourceDir as well as destinationDir
			if (ltrim($destinationDir, '/') === '' && strtolower($sourceNode->getName()) === 'shared') {
				throw new \Sabre_DAV_Exception_Forbidden();
			}
			if (!$this->fileView->isUpdatable($sourceDir)) {
				throw new \Sabre_DAV_Exception_Forbidden();
			}
			if (!$this->fileView->isUpdatable($destinationDir)) {
				throw new \Sabre_DAV_Exception_Forbidden();
			}
			if (!$this->fileView->isDeletable($sourcePath)) {
				throw new \Sabre_DAV_Exception_Forbidden();
			}
		}

		$fileName = basename($destinationPath);
		if (!\OCP\Util::isValidFileName($fileName)) {
			throw new \Sabre_DAV_Exception_BadRequest();
		}

		$renameOkay = $this->fileView->rename($sourcePath, $destinationPath);
		if (!$renameOkay) {
			throw new \Sabre_DAV_Exception_Forbidden('');
		}

		// update properties
		$query = \OC_DB::prepare('UPDATE `*PREFIX*properties` SET `propertypath` = ?'
			. ' WHERE `userid` = ? AND `propertypath` = ?');
		$query->execute(array(\OC\Files\Filesystem::normalizePath($destinationPath), \OC_User::getUser(), \OC\Files\Filesystem::normalizePath($sourcePath)));

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
	 * @throws \Sabre_DAV_Exception_ServiceUnavailable
	 * @return void
	 */
	public function copy($source, $destination) {
		if (!$this->fileView) {
			throw new \Sabre_DAV_Exception_ServiceUnavailable('filesystem not setup');
		}

		if ($this->fileView->is_file($source)) {
			$this->fileView->copy($source, $destination);
		} else {
			$this->fileView->mkdir($destination);
			$dh = $this->fileView->opendir($source);
			if (is_resource($dh)) {
				while (($subNode = readdir($dh)) !== false) {

					if ($subNode == '.' || $subNode == '..') continue;
					$this->copy($source . '/' . $subNode, $destination . '/' . $subNode);

				}
			}
		}

		list($destinationDir,) = \Sabre_DAV_URLUtil::splitPath($destination);
		$this->markDirty($destinationDir);
	}
}
