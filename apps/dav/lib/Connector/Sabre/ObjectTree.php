<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Connector\Sabre;

use Exception;
use OC\Files\FileInfo;
use OC\Files\Storage\FailedStorage;
use OC\Files\Storage\Storage;
use OC\Files\View;
use OC_FileChunking;
use OCA\DAV\Connector\Sabre\Exception\FileLocked;
use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCP\Files\ForbiddenException;
use OCP\Files\InvalidPathException;
use OCP\Files\Mount\IMountManager;
use OCP\Files\StorageInvalidException;
use OCP\Files\StorageNotAvailableException;
use OCP\Lock\LockedException;
use Sabre\DAV\Exception\Locked;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\DAV\INode;

class ObjectTree extends CachingTree {
	protected View $fileView;
	protected IMountManager $mountManager;

	/**
	 * @param INode $rootNode
	 * @param View $view
	 * @param  IMountManager $mountManager
	 */
	public function init(INode $rootNode, View $view, IMountManager $mountManager) {
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
	private function resolveChunkFile(string $path): string {
		if (isset($_SERVER['HTTP_OC_CHUNKED'])) {
			// resolve to real file name to find the proper node
			[$dir, $name] = \Sabre\Uri\split($path);
			if ($dir === '/' || $dir === '.') {
				$dir = '';
			}

			$info = OC_FileChunking::decodeName($name);
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
	 * @return INode
	 * @throws InvalidPath
	 * @throws Locked
	 * @throws NotFound
	 * @throws ServiceUnavailable|\Sabre\DAV\Exception\Forbidden
	 */
	public function getNodeForPath($path) {
		if (!$this->fileView) {
			throw new ServiceUnavailable('filesystem not setup');
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
			// resolve chunk file name to real name, if applicable
			$path = $this->resolveChunkFile($path);

			// read from cache
			try {
				$info = $this->fileView->getFileInfo($path);

				if ($info instanceof \OCP\Files\FileInfo && $info->getStorage()->instanceOfStorage(FailedStorage::class)) {
					throw new StorageNotAvailableException();
				}
			} catch (StorageNotAvailableException $e) {
				throw new ServiceUnavailable('Storage is temporarily not available', 0, $e);
			} catch (StorageInvalidException $e) {
				throw new NotFound('Storage ' . $path . ' is invalid');
			} catch (LockedException $e) {
				throw new Locked();
			} catch (ForbiddenException $e) {
				throw new \Sabre\DAV\Exception\Forbidden();
			}
		}

		if (!$info) {
			throw new NotFound('File with name ' . $path . ' could not be located');
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
	 * @throws Exception
	 * @throws \Sabre\DAV\Exception\Forbidden
	 * @throws Locked
	 * @throws NotFound
	 * @throws ServiceUnavailable
	 * @return void
	 */
	public function copy($sourcePath, $destinationPath) {
		if (!$this->fileView) {
			throw new ServiceUnavailable('filesystem not setup');
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
			throw new ServiceUnavailable($e->getMessage());
		} catch (ForbiddenException $ex) {
			throw new Forbidden($ex->getMessage(), $ex->getRetry());
		} catch (LockedException $e) {
			throw new FileLocked($e->getMessage(), $e->getCode(), $e);
		}

		[$destinationDir,] = \Sabre\Uri\split($destinationPath);
		$this->markDirty($destinationDir);
	}
}
