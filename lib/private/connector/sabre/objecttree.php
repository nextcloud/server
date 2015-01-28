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
use OC\Files\Mount\MoveableMount;
use OCP\Files\StorageInvalidException;
use OCP\Files\StorageNotAvailableException;

class ObjectTree extends \Sabre\DAV\ObjectTree {

	/**
	 * @var \OC\Files\View
	 */
	protected $fileView;

	/**
	 * @var \OC\Files\Mount\Manager
	 */
	protected $mountManager;

	/**
	 * Creates the object
	 *
	 * This method expects the rootObject to be passed as a parameter
	 */
	public function __construct() {
	}

	/**
	 * @param \Sabre\DAV\INode $rootNode
	 * @param \OC\Files\View $view
	 * @param \OC\Files\Mount\Manager $mountManager
	 */
	public function init(\Sabre\DAV\INode $rootNode, \OC\Files\View $view, \OC\Files\Mount\Manager $mountManager) {
		$this->rootNode = $rootNode;
		$this->fileView = $view;
		$this->mountManager = $mountManager;
	}

	/**
	 * Returns the INode object for the requested path
	 *
	 * @param string $path
	 * @throws \Sabre\DAV\Exception\ServiceUnavailable
	 * @throws \Sabre\DAV\Exception\NotFound
	 * @return \Sabre\DAV\INode
	 */
	public function getNodeForPath($path) {
		if (!$this->fileView) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable('filesystem not setup');
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
			$mount = $this->fileView->getMount($path);
			$storage = $mount->getStorage();
			$internalPath = $mount->getInternalPath($absPath);
			if ($storage) {
				/**
				 * @var \OC\Files\Storage\Storage $storage
				 */
				$scanner = $storage->getScanner($internalPath);
				// get data directly
				$data = $scanner->getData($internalPath);
				$info = new FileInfo($absPath, $storage, $internalPath, $data, $mount);
			} else {
				$info = null;
			}
		} else {
			// read from cache
			try {
				$info = $this->fileView->getFileInfo($path);
			} catch (StorageNotAvailableException $e) {
				throw new \Sabre\DAV\Exception\ServiceUnavailable('Storage not available');
			} catch (StorageInvalidException $e){
				throw new \Sabre\DAV\Exception\NotFound('Storage ' . $path . ' is invalid');
			}
		}

		if (!$info) {
			throw new \Sabre\DAV\Exception\NotFound('File with name ' . $path . ' could not be located');
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
	 * @throws \Sabre\DAV\Exception\BadRequest
	 * @throws \Sabre\DAV\Exception\ServiceUnavailable
	 * @throws \Sabre\DAV\Exception\Forbidden
	 * @return int
	 */
	public function move($sourcePath, $destinationPath) {
		if (!$this->fileView) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable('filesystem not setup');
		}

		$sourceNode = $this->getNodeForPath($sourcePath);
		if ($sourceNode instanceof \Sabre\DAV\ICollection and $this->nodeExists($destinationPath)) {
			throw new \Sabre\DAV\Exception\Forbidden('Could not copy directory ' . $sourceNode . ', target exists');
		}
		list($sourceDir,) = \Sabre\DAV\URLUtil::splitPath($sourcePath);
		list($destinationDir,) = \Sabre\DAV\URLUtil::splitPath($destinationPath);

		$isMovableMount = false;
		$sourceMount = $this->mountManager->find($this->fileView->getAbsolutePath($sourcePath));
		$internalPath = $sourceMount->getInternalPath($this->fileView->getAbsolutePath($sourcePath));
		if ($sourceMount instanceof MoveableMount && $internalPath === '') {
			$isMovableMount = true;
		}

		try {
			// check update privileges
			if (!$this->fileView->isUpdatable($sourcePath) && !$isMovableMount) {
				throw new \Sabre\DAV\Exception\Forbidden();
			}
			if ($sourceDir !== $destinationDir) {
				if (!$this->fileView->isCreatable($destinationDir)) {
					throw new \Sabre\DAV\Exception\Forbidden();
				}
				if (!$this->fileView->isDeletable($sourcePath) && !$isMovableMount) {
					throw new \Sabre\DAV\Exception\Forbidden();
				}
			}

			$fileName = basename($destinationPath);
			if (!\OCP\Util::isValidFileName($fileName)) {
				throw new \Sabre\DAV\Exception\BadRequest();
			}

			$renameOkay = $this->fileView->rename($sourcePath, $destinationPath);
			if (!$renameOkay) {
				throw new \Sabre\DAV\Exception\Forbidden('');
			}
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable($e->getMessage());
		}

		// update properties
		$query = \OC_DB::prepare('UPDATE `*PREFIX*properties` SET `propertypath` = ?'
			. ' WHERE `userid` = ? AND `propertypath` = ?');
		$query->execute(array(\OC\Files\Filesystem::normalizePath($destinationPath), \OC_User::getUser(),
			\OC\Files\Filesystem::normalizePath($sourcePath)));

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
	 * @throws \Sabre\DAV\Exception\ServiceUnavailable
	 * @return void
	 */
	public function copy($source, $destination) {
		if (!$this->fileView) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable('filesystem not setup');
		}

		try {
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
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable($e->getMessage());
		}

		list($destinationDir,) = \Sabre\DAV\URLUtil::splitPath($destination);
		$this->markDirty($destinationDir);
	}
}
