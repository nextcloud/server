<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use OC\Files\FileInfo;
use OC\Files\Storage\FailedStorage;
use OC\Files\Storage\Storage;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\Exception\FileLocked;
use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCP\Files\ForbiddenException;
use OCP\Files\InvalidPathException;
use OCP\Files\Mount\IMountManager;
use OCP\Files\StorageInvalidException;
use OCP\Files\StorageNotAvailableException;
use OCP\Lock\LockedException;

class ObjectTree extends CachingTree {

	/**
	 * @var View
	 */
	protected $fileView;

	/**
	 * @var IMountManager
	 */
	protected $mountManager;

	/**
	 * Creates the object
	 */
	public function __construct() {
	}

	/**
	 * @param \Sabre\DAV\INode $rootNode
	 * @param View $view
	 * @param IMountManager $mountManager
	 */
	public function init(\Sabre\DAV\INode $rootNode, View $view, IMountManager $mountManager) {
		$this->rootNode = $rootNode;
		$this->fileView = $view;
		$this->mountManager = $mountManager;
	}

	/**
	 * Returns the INode object for the requested path
	 *
	 * @param string $path
	 * @return \Sabre\DAV\INode
	 * @throws InvalidPath
	 * @throws \Sabre\DAV\Exception\Locked
	 * @throws \Sabre\DAV\Exception\NotFound
	 * @throws \Sabre\DAV\Exception\ServiceUnavailable
	 */
	public function getNodeForPath($path) {
		if (!$this->fileView) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable('filesystem not setup');
		}

		$path = trim($path, '/');

		if (isset($this->cache[$path])) {
			return $this->cache[$path];
		}

		if ($path) {
			try {
				$this->fileView->verifyPath($path, basename($path));
			} catch (InvalidPathException $ex) {
				throw new InvalidPath($ex->getMessage());
			}
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
			if ($storage && $storage->file_exists($internalPath)) {
				/**
				 * @var Storage $storage
				 */
				// get data directly
				$data = $storage->getMetaData($internalPath);
				$info = new FileInfo($absPath, $storage, $internalPath, $data, $mount);
			} else {
				$info = null;
			}
		} else {
			// read from cache
			try {
				$info = $this->fileView->getFileInfo($path);

				if ($info instanceof \OCP\Files\FileInfo && $info->getStorage()->instanceOfStorage(FailedStorage::class)) {
					throw new StorageNotAvailableException();
				}
			} catch (StorageNotAvailableException $e) {
				throw new \Sabre\DAV\Exception\ServiceUnavailable('Storage is temporarily not available', 0, $e);
			} catch (StorageInvalidException $e) {
				throw new \Sabre\DAV\Exception\NotFound('Storage ' . $path . ' is invalid');
			} catch (LockedException $e) {
				throw new \Sabre\DAV\Exception\Locked();
			} catch (ForbiddenException $e) {
				throw new \Sabre\DAV\Exception\Forbidden();
			}
		}

		if (!$info) {
			throw new \Sabre\DAV\Exception\NotFound('File with name ' . $path . ' could not be located');
		}

		if ($info->getType() === 'dir') {
			$node = new Directory($this->fileView, $info, $this);
		} else {
			$node = new File($this->fileView, $info);
		}

		$this->cache[$path] = $node;
		return $node;
	}

	/**
	 * Copies a file or directory.
	 *
	 * This method must work recursively and delete the destination
	 * if it exists
	 *
	 * @param string $sourcePath
	 * @param string $destinationPath
	 * @throws FileLocked
	 * @throws Forbidden
	 * @throws InvalidPath
	 * @throws \Exception
	 * @throws \Sabre\DAV\Exception\Forbidden
	 * @throws \Sabre\DAV\Exception\Locked
	 * @throws \Sabre\DAV\Exception\NotFound
	 * @throws \Sabre\DAV\Exception\ServiceUnavailable
	 * @return void
	 */
	public function copy($sourcePath, $destinationPath) {
		if (!$this->fileView) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable('filesystem not setup');
		}


		$info = $this->fileView->getFileInfo(dirname($destinationPath));
		if ($this->fileView->file_exists($destinationPath)) {
			$destinationPermission = $info && $info->isUpdateable();
		} else {
			$destinationPermission = $info && $info->isCreatable();
		}
		if (!$destinationPermission) {
			throw new Forbidden('No permissions to copy object.');
		}

		// this will trigger existence check
		$this->getNodeForPath($sourcePath);

		[$destinationDir, $destinationName] = \Sabre\Uri\split($destinationPath);
		try {
			$this->fileView->verifyPath($destinationDir, $destinationName);
		} catch (InvalidPathException $ex) {
			throw new InvalidPath($ex->getMessage());
		}

		try {
			$this->fileView->copy($sourcePath, $destinationPath);
		} catch (StorageNotAvailableException $e) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable($e->getMessage());
		} catch (ForbiddenException $ex) {
			throw new Forbidden($ex->getMessage(), $ex->getRetry());
		} catch (LockedException $e) {
			throw new FileLocked($e->getMessage(), $e->getCode(), $e);
		}

		[$destinationDir,] = \Sabre\Uri\split($destinationPath);
		$this->markDirty($destinationDir);
	}
}
