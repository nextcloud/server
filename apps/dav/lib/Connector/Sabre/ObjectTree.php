<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Connector\Sabre;

use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCA\DAV\Connector\Sabre\Exception\FileLocked;
use OC\Files\FileInfo;
use OC\Files\Mount\MoveableMount;
use OCP\Files\ForbiddenException;
use OCP\Files\StorageInvalidException;
use OCP\Files\StorageNotAvailableException;
use OCP\Lock\LockedException;

class ObjectTree extends \Sabre\DAV\Tree {

	/**
	 * @var \OC\Files\View
	 */
	protected $fileView;

	/**
	 * @var  \OCP\Files\Mount\IMountManager
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
	 * @param  \OCP\Files\Mount\IMountManager $mountManager
	 */
	public function init(\Sabre\DAV\INode $rootNode, \OC\Files\View $view, \OCP\Files\Mount\IMountManager $mountManager) {
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

	public function cacheNode(Node $node) {
		$this->cache[trim($node->getPath(), '/')] = $node;
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
			} catch (\OCP\Files\InvalidPathException $ex) {
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
				 * @var \OC\Files\Storage\Storage $storage
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
			} catch (StorageNotAvailableException $e) {
				throw new \Sabre\DAV\Exception\ServiceUnavailable('Storage not available');
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
			$node = new \OCA\DAV\Connector\Sabre\Directory($this->fileView, $info, $this);
		} else {
			$node = new \OCA\DAV\Connector\Sabre\File($this->fileView, $info);
		}

		$this->cache[$path] = $node;
		return $node;

	}

	/**
	 * Moves a file from one location to another
	 *
	 * @param string $sourcePath The path to the file which should be moved
	 * @param string $destinationPath The full destination path, so not just the destination parent node
	 * @throws FileLocked
	 * @throws Forbidden
	 * @throws InvalidPath
	 * @throws \Sabre\DAV\Exception\Forbidden
	 * @throws \Sabre\DAV\Exception\Locked
	 * @throws \Sabre\DAV\Exception\NotFound
	 * @throws \Sabre\DAV\Exception\ServiceUnavailable
	 * @return int
	 */
	public function move($sourcePath, $destinationPath) {
		if (!$this->fileView) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable('filesystem not setup');
		}

		$infoDestination = $this->fileView->getFileInfo(dirname($destinationPath));
		if (dirname($destinationPath) === dirname($sourcePath)) {
			$sourcePermission = $infoDestination && $infoDestination->isUpdateable();
			$destinationPermission = $sourcePermission;
		} else {
			$infoSource = $this->fileView->getFileInfo($sourcePath);
			if ($this->fileView->file_exists($destinationPath)) {
				$destinationPermission = $infoDestination && $infoDestination->isUpdateable();
			} else {
				$destinationPermission = $infoDestination && $infoDestination->isCreatable();
			}
			$sourcePermission =  $infoSource && $infoSource->isDeletable();
		}

		if (!$destinationPermission || !$sourcePermission) {
			throw new Forbidden('No permissions to move object.');
		}

		$targetNodeExists = $this->nodeExists($destinationPath);
		$sourceNode = $this->getNodeForPath($sourcePath);
		if ($sourceNode instanceof \Sabre\DAV\ICollection && $targetNodeExists) {
			throw new \Sabre\DAV\Exception\Forbidden('Could not copy directory ' . $sourceNode->getName() . ', target exists');
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
		} catch (StorageNotAvailableException $e) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable($e->getMessage());
		} catch (ForbiddenException $ex) {
			throw new Forbidden($ex->getMessage(), $ex->getRetry());
		} catch (LockedException $e) {
			throw new FileLocked($e->getMessage(), $e->getCode(), $e);
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
	public function copy($source, $destination) {
		if (!$this->fileView) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable('filesystem not setup');
		}


		$info = $this->fileView->getFileInfo(dirname($destination));
		if ($this->fileView->file_exists($destination)) {
			$destinationPermission = $info && $info->isUpdateable();
		} else {
			$destinationPermission = $info && $info->isCreatable();
		}
		if (!$destinationPermission) {
			throw new Forbidden('No permissions to copy object.');
		}

		// this will trigger existence check
		$this->getNodeForPath($source);

		list($destinationDir, $destinationName) = \Sabre\HTTP\URLUtil::splitPath($destination);
		try {
			$this->fileView->verifyPath($destinationDir, $destinationName);
		} catch (\OCP\Files\InvalidPathException $ex) {
			throw new InvalidPath($ex->getMessage());
		}

		try {
			$this->fileView->copy($source, $destination);
		} catch (StorageNotAvailableException $e) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable($e->getMessage());
		} catch (ForbiddenException $ex) {
			throw new Forbidden($ex->getMessage(), $ex->getRetry());
		} catch (LockedException $e) {
			throw new FileLocked($e->getMessage(), $e->getCode(), $e);
		}

		list($destinationDir,) = \Sabre\HTTP\URLUtil::splitPath($destination);
		$this->markDirty($destinationDir);
	}
}
