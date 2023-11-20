<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Klaas Freitag <freitag@owncloud.com>
 * @author Markus Goetz <markus@woboq.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tobias Kaminsky <tobias@kaminsky.me>
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

use OC\Files\Mount\MoveableMount;
use OC\Files\Node\File;
use OC\Files\Node\Folder;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCP\Files\DavUtil;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\Files\StorageNotAvailableException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;

abstract class Node implements \Sabre\DAV\INode {
	/**
	 * @var View
	 */
	protected $fileView;

	/**
	 * The path to the current node
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * node properties cache
	 *
	 * @var array
	 */
	protected $property_cache = null;

	protected FileInfo $info;

	/**
	 * @var IManager
	 */
	protected $shareManager;

	protected \OCP\Files\Node $node;

	/**
	 * Sets up the node, expects a full path name
	 */
	public function __construct(View $view, FileInfo $info, IManager $shareManager = null) {
		$this->fileView = $view;
		$this->path = $this->fileView->getRelativePath($info->getPath());
		$this->info = $info;
		if ($shareManager) {
			$this->shareManager = $shareManager;
		} else {
			$this->shareManager = \OC::$server->getShareManager();
		}
		if ($info instanceof Folder || $info instanceof File) {
			$this->node = $info;
		} else {
			// The Node API assumes that the view passed doesn't have a fake root
			$rootView = \OC::$server->get(View::class);
			$root = \OC::$server->get(IRootFolder::class);
			if ($info->getType() === FileInfo::TYPE_FOLDER) {
				$this->node = new Folder($root, $rootView, $this->fileView->getAbsolutePath($this->path), $info);
			} else {
				$this->node = new File($root, $rootView, $this->fileView->getAbsolutePath($this->path), $info);
			}
		}
	}

	protected function refreshInfo(): void {
		$info = $this->fileView->getFileInfo($this->path);
		if ($info === false) {
			throw new \Sabre\DAV\Exception('Failed to get fileinfo for '. $this->path);
		}
		$this->info = $info;
		$root = \OC::$server->get(IRootFolder::class);
		$rootView = \OC::$server->get(View::class);
		if ($this->info->getType() === FileInfo::TYPE_FOLDER) {
			$this->node = new Folder($root, $rootView, $this->path, $this->info);
		} else {
			$this->node = new File($root, $rootView, $this->path, $this->info);
		}
	}

	/**
	 *  Returns the name of the node
	 *
	 * @return string
	 */
	public function getName() {
		return $this->info->getName();
	}

	/**
	 * Returns the full path
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Renames the node
	 *
	 * @param string $name The new name
	 * @throws \Sabre\DAV\Exception\BadRequest
	 * @throws \Sabre\DAV\Exception\Forbidden
	 */
	public function setName($name) {
		// rename is only allowed if the update privilege is granted
		if (!($this->info->isUpdateable() || ($this->info->getMountPoint() instanceof MoveableMount && $this->info->getInternalPath() === ''))) {
			throw new \Sabre\DAV\Exception\Forbidden();
		}

		[$parentPath,] = \Sabre\Uri\split($this->path);
		[, $newName] = \Sabre\Uri\split($name);

		// verify path of the target
		$this->verifyPath();

		$newPath = $parentPath . '/' . $newName;

		if (!$this->fileView->rename($this->path, $newPath)) {
			throw new \Sabre\DAV\Exception('Failed to rename '. $this->path . ' to ' . $newPath);
		}

		$this->path = $newPath;

		$this->refreshInfo();
	}

	public function setPropertyCache($property_cache) {
		$this->property_cache = $property_cache;
	}

	/**
	 * Returns the last modification time, as a unix timestamp
	 *
	 * @return int timestamp as integer
	 */
	public function getLastModified() {
		$timestamp = $this->info->getMtime();
		if (!empty($timestamp)) {
			return (int)$timestamp;
		}
		return $timestamp;
	}

	/**
	 *  sets the last modification time of the file (mtime) to the value given
	 *  in the second parameter or to now if the second param is empty.
	 *  Even if the modification time is set to a custom value the access time is set to now.
	 */
	public function touch($mtime) {
		$mtime = $this->sanitizeMtime($mtime);
		$this->fileView->touch($this->path, $mtime);
		$this->refreshInfo();
	}

	/**
	 * Returns the ETag for a file
	 *
	 * An ETag is a unique identifier representing the current version of the
	 * file. If the file changes, the ETag MUST change.  The ETag is an
	 * arbitrary string, but MUST be surrounded by double-quotes.
	 *
	 * Return null if the ETag can not effectively be determined
	 *
	 * @return string
	 */
	public function getETag() {
		return '"' . $this->info->getEtag() . '"';
	}

	/**
	 * Sets the ETag
	 *
	 * @param string $etag
	 *
	 * @return int file id of updated file or -1 on failure
	 */
	public function setETag($etag) {
		return $this->fileView->putFileInfo($this->path, ['etag' => $etag]);
	}

	public function setCreationTime(int $time) {
		return $this->fileView->putFileInfo($this->path, ['creation_time' => $time]);
	}

	public function setUploadTime(int $time) {
		return $this->fileView->putFileInfo($this->path, ['upload_time' => $time]);
	}

	/**
	 * Returns the size of the node, in bytes
	 *
	 * @psalm-suppress ImplementedReturnTypeMismatch \Sabre\DAV\IFile::getSize signature does not support 32bit
	 * @return int|float
	 */
	public function getSize(): int|float {
		return $this->info->getSize();
	}

	/**
	 * Returns the cache's file id
	 *
	 * @return int
	 */
	public function getId() {
		return $this->info->getId();
	}

	/**
	 * @return string|null
	 */
	public function getFileId() {
		if ($id = $this->info->getId()) {
			return DavUtil::getDavFileId($id);
		}

		return null;
	}

	/**
	 * @return integer
	 */
	public function getInternalFileId() {
		return $this->info->getId();
	}

	public function getInternalPath(): string {
		return $this->info->getInternalPath();
	}

	/**
	 * @param string $user
	 * @return int
	 */
	public function getSharePermissions($user) {
		// check of we access a federated share
		if ($user !== null) {
			try {
				$share = $this->shareManager->getShareByToken($user);
				return $share->getPermissions();
			} catch (ShareNotFound $e) {
				// ignore
			}
		}

		try {
			$storage = $this->info->getStorage();
		} catch (StorageNotAvailableException $e) {
			$storage = null;
		}

		if ($storage && $storage->instanceOfStorage('\OCA\Files_Sharing\SharedStorage')) {
			/** @var \OCA\Files_Sharing\SharedStorage $storage */
			$permissions = (int)$storage->getShare()->getPermissions();
		} else {
			$permissions = $this->info->getPermissions();
		}

		/*
		 * We can always share non moveable mount points with DELETE and UPDATE
		 * Eventually we need to do this properly
		 */
		$mountpoint = $this->info->getMountPoint();
		if (!($mountpoint instanceof MoveableMount)) {
			$mountpointpath = $mountpoint->getMountPoint();
			if (substr($mountpointpath, -1) === '/') {
				$mountpointpath = substr($mountpointpath, 0, -1);
			}

			if (!$mountpoint->getOption('readonly', false) && $mountpointpath === $this->info->getPath()) {
				$permissions |= \OCP\Constants::PERMISSION_DELETE | \OCP\Constants::PERMISSION_UPDATE;
			}
		}

		/*
		 * Files can't have create or delete permissions
		 */
		if ($this->info->getType() === \OCP\Files\FileInfo::TYPE_FILE) {
			$permissions &= ~(\OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_DELETE);
		}

		return $permissions;
	}

	/**
	 * @return array
	 */
	public function getShareAttributes(): array {
		$attributes = [];

		try {
			$storage = $this->info->getStorage();
		} catch (StorageNotAvailableException $e) {
			$storage = null;
		}

		if ($storage && $storage->instanceOfStorage(\OCA\Files_Sharing\SharedStorage::class)) {
			/** @var \OCA\Files_Sharing\SharedStorage $storage */
			$attributes = $storage->getShare()->getAttributes();
			if ($attributes === null) {
				return [];
			} else {
				return $attributes->toArray();
			}
		}

		return $attributes;
	}

	/**
	 * @param string $user
	 * @return string
	 */
	public function getNoteFromShare($user) {
		if ($user === null) {
			return '';
		}

		// Retrieve note from the share object already loaded into
		// memory, to avoid additional database queries.
		$storage = $this->getNode()->getStorage();
		if (!$storage->instanceOfStorage(\OCA\Files_Sharing\SharedStorage::class)) {
			return '';
		}
		/** @var \OCA\Files_Sharing\SharedStorage $storage */

		$share = $storage->getShare();
		$note = $share->getNote();
		if ($share->getShareOwner() !== $user) {
			return $note;
		}
		return '';
	}

	/**
	 * @return string
	 */
	public function getDavPermissions() {
		return DavUtil::getDavPermissions($this->info);
	}

	public function getOwner() {
		return $this->info->getOwner();
	}

	protected function verifyPath() {
		try {
			$fileName = basename($this->info->getPath());
			$this->fileView->verifyPath($this->path, $fileName);
		} catch (\OCP\Files\InvalidPathException $ex) {
			throw new InvalidPath($ex->getMessage());
		}
	}

	/**
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 */
	public function acquireLock($type) {
		$this->fileView->lockFile($this->path, $type);
	}

	/**
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 */
	public function releaseLock($type) {
		$this->fileView->unlockFile($this->path, $type);
	}

	/**
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 */
	public function changeLock($type) {
		$this->fileView->changeLock($this->path, $type);
	}

	public function getFileInfo() {
		return $this->info;
	}

	public function getNode(): \OCP\Files\Node {
		return $this->node;
	}

	protected function sanitizeMtime($mtimeFromRequest) {
		return MtimeSanitizer::sanitizeMtime($mtimeFromRequest);
	}
}
