<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Connector\Sabre;

use OC\Connector\Sabre\Exception\InvalidPath;
use OC\Files\FileInfo;
use OC\Files\Filesystem;
use OC\Files\Mount\MoveableMount;
use OC_Connector_Sabre_Exception_InvalidPath;
use OCP\Files\StorageInvalidException;
use OCP\Files\StorageNotAvailableException;

class ObjectTree extends \Sabre\DAV\Tree {

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
	 * If the given path is a chunked file name, converts it
	 * to the real file name. Only applies if the OC-CHUNKED header
	 * is present.
	 *
	 * @param string $path chunk file path to convert
	 * 
	 * @return string path to real file
	 */
	private function resolveChunkFile($path) {
		if (isset($_SERVER['HTTP_OC_CHUNKED'])) {
			// resolve to real file name to find the proper node
			list($dir, $name) = \Sabre\HTTP\URLUtil::splitPath($path);
			if ($dir == '/' || $dir == '.') {
				$dir = '';
			}

			$info = \OC_FileChunking::decodeName($name);
			// only replace path if it was really the chunked file
			if (isset($info['transferid'])) {
				// getNodePath is called for multiple nodes within a chunk
				// upload call
				$path = $dir . '/' . $info['name'];
				$path = ltrim($path, '/');
			}
		}
		return $path;
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
			// resolve chunk file name to real name, if applicable
			$path = $this->resolveChunkFile($path);

			// read from cache
			try {
				$info = $this->fileView->getFileInfo($path);
			} catch (StorageNotAvailableException $e) {
				throw new \Sabre\DAV\Exception\ServiceUnavailable('Storage not available');
			} catch (StorageInvalidException $e) {
				throw new \Sabre\DAV\Exception\NotFound('Storage ' . $path . ' is invalid');
			}
		}

		if (!$info) {
			throw new \Sabre\DAV\Exception\NotFound('File with name ' . $path . ' could not be located');
		}

		if ($info->getType() === 'dir') {
			$node = new \OC\Connector\Sabre\Directory($this->fileView, $info);
		} else {
			$node = new \OC\Connector\Sabre\File($this->fileView, $info);
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

		$targetNodeExists = $this->nodeExists($destinationPath);
		$sourceNode = $this->getNodeForPath($sourcePath);
		if ($sourceNode instanceof \Sabre\DAV\ICollection && $targetNodeExists) {
			throw new \Sabre\DAV\Exception\Forbidden('Could not copy directory ' . $sourceNode . ', target exists');
		}
		list($sourceDir,) = \Sabre\HTTP\URLUtil::splitPath($sourcePath);
		list($destinationDir,) = \Sabre\HTTP\URLUtil::splitPath($destinationPath);

		$isMovableMount = false;
		$sourceMount = $this->mountManager->find($this->fileView->getAbsolutePath($sourcePath));
		$internalPath = $sourceMount->getInternalPath($this->fileView->getAbsolutePath($sourcePath));
		if ($sourceMount instanceof MoveableMount && $internalPath === '') {
			$isMovableMount = true;
		}

		try {
			$sameFolder = ($sourceDir === $destinationDir);
			// if we're overwriting or same folder
			if ($targetNodeExists || $sameFolder) {
				// note that renaming a share mount point is always allowed
				if (!$this->fileView->isUpdatable($destinationDir) && !$isMovableMount) {
					throw new \Sabre\DAV\Exception\Forbidden();
				}
			} else {
				if (!$this->fileView->isCreatable($destinationDir)) {
					throw new \Sabre\DAV\Exception\Forbidden();
				}
			}

			if (!$sameFolder) {
				// moving to a different folder, source will be gone, like a deletion
				// note that moving a share mount point is always allowed
				if (!$this->fileView->isDeletable($sourcePath) && !$isMovableMount) {
					throw new \Sabre\DAV\Exception\Forbidden();
				}
			}

			$fileName = basename($destinationPath);
			try {
				$this->fileView->verifyPath($destinationDir, $fileName);
			} catch (\OCP\Files\InvalidPathException $ex) {
				throw new InvalidPath($ex->getMessage());
			}

			$renameOkay = $this->fileView->rename($sourcePath, $destinationPath);
			if (!$renameOkay) {
				throw new \Sabre\DAV\Exception\Forbidden('');
			}
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable($e->getMessage());
		}

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

		// this will trigger existence check
		$this->getNodeForPath($source);

		try {
			$this->fileView->copy($source, $destination);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable($e->getMessage());
		}

		list($destinationDir,) = \Sabre\HTTP\URLUtil::splitPath($destination);
		$this->markDirty($destinationDir);
	}
}
