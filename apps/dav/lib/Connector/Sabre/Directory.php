<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
use OCP\Files\ForbiddenException;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use Sabre\DAV\Exception\Locked;

class Directory extends \OCA\DAV\Connector\Sabre\Node
	implements \Sabre\DAV\ICollection, \Sabre\DAV\IQuota {

	/**
	 * Cached directory content
	 *
	 * @var \OCP\Files\FileInfo[]
	 */
	private $dirContent;

	/**
	 * Cached quota info
	 *
	 * @var array
	 */
	private $quotaInfo;

	/**
	 * @var ObjectTree|null
	 */
	private $tree;

	/**
	 * Sets up the node, expects a full path name
	 *
	 * @param \OC\Files\View $view
	 * @param \OCP\Files\FileInfo $info
	 * @param ObjectTree|null $tree
	 * @param \OCP\Share\IManager $shareManager
	 */
	public function __construct($view, $info, $tree = null, $shareManager = null) {
		parent::__construct($view, $info, $shareManager);
		$this->tree = $tree;
	}

	/**
	 * Creates a new file in the directory
	 *
	 * Data will either be supplied as a stream resource, or in certain cases
	 * as a string. Keep in mind that you may have to support either.
	 *
	 * After successful creation of the file, you may choose to return the ETag
	 * of the new file here.
	 *
	 * The returned ETag must be surrounded by double-quotes (The quotes should
	 * be part of the actual string).
	 *
	 * If you cannot accurately determine the ETag, you should not return it.
	 * If you don't store the file exactly as-is (you're transforming it
	 * somehow) you should also not return an ETag.
	 *
	 * This means that if a subsequent GET to this new file does not exactly
	 * return the same contents of what was submitted here, you are strongly
	 * recommended to omit the ETag.
	 *
	 * @param string $name Name of the file
	 * @param resource|string $data Initial payload
	 * @return null|string
	 * @throws Exception\EntityTooLarge
	 * @throws Exception\UnsupportedMediaType
	 * @throws FileLocked
	 * @throws InvalidPath
	 * @throws \Sabre\DAV\Exception
	 * @throws \Sabre\DAV\Exception\BadRequest
	 * @throws \Sabre\DAV\Exception\Forbidden
	 * @throws \Sabre\DAV\Exception\ServiceUnavailable
	 */
	public function createFile($name, $data = null) {

		try {
			// for chunked upload also updating a existing file is a "createFile"
			// because we create all the chunks before re-assemble them to the existing file.
			if (isset($_SERVER['HTTP_OC_CHUNKED'])) {

				// exit if we can't create a new file and we don't updatable existing file
				$info = \OC_FileChunking::decodeName($name);
				if (!$this->fileView->isCreatable($this->path) &&
					!$this->fileView->isUpdatable($this->path . '/' . $info['name'])
				) {
					throw new \Sabre\DAV\Exception\Forbidden();
				}

			} else {
				// For non-chunked upload it is enough to check if we can create a new file
				if (!$this->fileView->isCreatable($this->path)) {
					throw new \Sabre\DAV\Exception\Forbidden();
				}
			}

			$this->fileView->verifyPath($this->path, $name);

			$path = $this->fileView->getAbsolutePath($this->path) . '/' . $name;
			// using a dummy FileInfo is acceptable here since it will be refreshed after the put is complete
			$info = new \OC\Files\FileInfo($path, null, null, array(), null);
			$node = new \OCA\DAV\Connector\Sabre\File($this->fileView, $info);
			$node->acquireLock(ILockingProvider::LOCK_SHARED);
			return $node->put($data);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable($e->getMessage());
		} catch (\OCP\Files\InvalidPathException $ex) {
			throw new InvalidPath($ex->getMessage());
		} catch (ForbiddenException $ex) {
			throw new Forbidden($ex->getMessage(), $ex->getRetry());
		} catch (LockedException $e) {
			throw new FileLocked($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Creates a new subdirectory
	 *
	 * @param string $name
	 * @throws FileLocked
	 * @throws InvalidPath
	 * @throws \Sabre\DAV\Exception\Forbidden
	 * @throws \Sabre\DAV\Exception\ServiceUnavailable
	 */
	public function createDirectory($name) {
		try {
			if (!$this->info->isCreatable()) {
				throw new \Sabre\DAV\Exception\Forbidden();
			}

			$this->fileView->verifyPath($this->path, $name);
			$newPath = $this->path . '/' . $name;
			if (!$this->fileView->mkdir($newPath)) {
				throw new \Sabre\DAV\Exception\Forbidden('Could not create directory ' . $newPath);
			}
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable($e->getMessage());
		} catch (\OCP\Files\InvalidPathException $ex) {
			throw new InvalidPath($ex->getMessage());
		} catch (ForbiddenException $ex) {
			throw new Forbidden($ex->getMessage(), $ex->getRetry());
		} catch (LockedException $e) {
			throw new FileLocked($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Returns a specific child node, referenced by its name
	 *
	 * @param string $name
	 * @param \OCP\Files\FileInfo $info
	 * @return \Sabre\DAV\INode
	 * @throws InvalidPath
	 * @throws \Sabre\DAV\Exception\NotFound
	 * @throws \Sabre\DAV\Exception\ServiceUnavailable
	 */
	public function getChild($name, $info = null) {
		$path = $this->path . '/' . $name;
		if (is_null($info)) {
			try {
				$this->fileView->verifyPath($this->path, $name);
				$info = $this->fileView->getFileInfo($path);
			} catch (\OCP\Files\StorageNotAvailableException $e) {
				throw new \Sabre\DAV\Exception\ServiceUnavailable($e->getMessage());
			} catch (\OCP\Files\InvalidPathException $ex) {
				throw new InvalidPath($ex->getMessage());
			} catch (ForbiddenException $e) {
				throw new \Sabre\DAV\Exception\Forbidden();
			}
		}

		if (!$info) {
			throw new \Sabre\DAV\Exception\NotFound('File with name ' . $path . ' could not be located');
		}

		if ($info['mimetype'] == 'httpd/unix-directory') {
			$node = new \OCA\DAV\Connector\Sabre\Directory($this->fileView, $info, $this->tree, $this->shareManager);
		} else {
			$node = new \OCA\DAV\Connector\Sabre\File($this->fileView, $info, $this->shareManager);
		}
		if ($this->tree) {
			$this->tree->cacheNode($node);
		}
		return $node;
	}

	/**
	 * Returns an array with all the child nodes
	 *
	 * @return \Sabre\DAV\INode[]
	 */
	public function getChildren() {
		if (!is_null($this->dirContent)) {
			return $this->dirContent;
		}
		try {
			$folderContent = $this->fileView->getDirectoryContent($this->path);
		} catch (LockedException $e) {
			throw new Locked();
		}

		$nodes = array();
		foreach ($folderContent as $info) {
			$node = $this->getChild($info->getName(), $info);
			$nodes[] = $node;
		}
		$this->dirContent = $nodes;
		return $this->dirContent;
	}

	/**
	 * Checks if a child exists.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function childExists($name) {
		// note: here we do NOT resolve the chunk file name to the real file name
		// to make sure we return false when checking for file existence with a chunk
		// file name.
		// This is to make sure that "createFile" is still triggered
		// (required old code) instead of "updateFile".
		//
		// TODO: resolve chunk file name here and implement "updateFile"
		$path = $this->path . '/' . $name;
		return $this->fileView->file_exists($path);

	}

	/**
	 * Deletes all files in this directory, and then itself
	 *
	 * @return void
	 * @throws FileLocked
	 * @throws \Sabre\DAV\Exception\Forbidden
	 */
	public function delete() {

		if ($this->path === '' || $this->path === '/' || !$this->info->isDeletable()) {
			throw new \Sabre\DAV\Exception\Forbidden();
		}

		try {
			if (!$this->fileView->rmdir($this->path)) {
				// assume it wasn't possible to remove due to permission issue
				throw new \Sabre\DAV\Exception\Forbidden();
			}
		} catch (ForbiddenException $ex) {
			throw new Forbidden($ex->getMessage(), $ex->getRetry());
		} catch (LockedException $e) {
			throw new FileLocked($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Returns available diskspace information
	 *
	 * @return array
	 */
	public function getQuotaInfo() {
		if ($this->quotaInfo) {
			return $this->quotaInfo;
		}
		try {
			$storageInfo = \OC_Helper::getStorageInfo($this->info->getPath(), $this->info);
			if ($storageInfo['quota'] === \OCP\Files\FileInfo::SPACE_UNLIMITED) {
				$free = \OCP\Files\FileInfo::SPACE_UNLIMITED;
			} else {
				$free = $storageInfo['free'];
			}
			$this->quotaInfo = array(
				$storageInfo['used'],
				$free
			);
			return $this->quotaInfo;
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			return array(0, 0);
		}
	}

}
