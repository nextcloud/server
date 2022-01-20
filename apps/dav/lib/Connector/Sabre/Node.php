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
use OC\Files\View;
use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCA\Files_Sharing\SharedStorage;
use OCP\Constants;
use OCP\Files\FileInfo;
use OCP\Files\InvalidPathException;
use OCP\Files\StorageNotAvailableException;
use OCP\IUser;
use OCP\Lock\LockedException;
use OCP\Share\IShare;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\INode;

abstract class Node implements INode {

	/**
	 * @var View
	 */
	protected $fileView;

	/**
	 * The path to the current node
	 *
	 * @var string|null
	 */
	protected $path;

	/**
	 * node properties cache
	 *
	 * @var array
	 */
	protected $property_cache = null;

	/**
	 * @var FileInfo|null
	 */
	protected $info;

	/**
	 * @var IManager
	 */
	protected $shareManager;

	/**
	 * Sets up the node, expects a full path name
	 *
	 * @param View $view
	 * @param FileInfo $info
	 * @param IManager|null $shareManager
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function __construct(View $view, FileInfo $info, IManager $shareManager = null) {
		$this->fileView = $view;
		$this->path = $this->fileView->getRelativePath($info->getPath());
		$this->info = $info;
		if ($shareManager) {
			$this->shareManager = $shareManager;
		} else {
			$this->shareManager = \OC::$server->get(IManager::class);
		}
	}

	protected function refreshInfo() {
		$this->info = $this->fileView->getFileInfo($this->path);
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
	public function getPath(): ?string {
		return $this->path;
	}

	/**
	 * Renames the node
	 *
	 * @param string $name The new name
	 * @throws InvalidPath
	 * @throws LockedException
	 * @throws Exception
	 * @throws Forbidden
	 */
	public function setName($name) {

		// rename is only allowed if the update privilege is granted
		if (!($this->info->isUpdateable() || ($this->info->getMountPoint() instanceof MoveableMount && $this->info->getInternalPath() === ''))) {
			throw new Forbidden();
		}

		[$parentPath,] = \Sabre\Uri\split($this->path);
		[, $newName] = \Sabre\Uri\split($name);

		// verify path of the target
		$this->verifyPath();

		$newPath = $parentPath . '/' . $newName;

		if (!$this->fileView->rename($this->path, $newPath)) {
			throw new Exception('Failed to rename '. $this->path . ' to ' . $newPath);
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
	public function touch(string $mtime): void {
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
	public function getETag(): string {
		return '"' . $this->info->getEtag() . '"';
	}

	/**
	 * Sets the ETag
	 *
	 * @param string $etag
	 *
	 * @return int file id of updated file or -1 on failure
	 */
	public function setETag(string $etag): int {
		return $this->fileView->putFileInfo($this->path, ['etag' => $etag]);
	}

	public function setCreationTime(int $time): int {
		return $this->fileView->putFileInfo($this->path, ['creation_time' => $time]);
	}

	public function setUploadTime(int $time): int {
		return $this->fileView->putFileInfo($this->path, ['upload_time' => $time]);
	}

	/**
	 * Returns the size of the node, in bytes
	 *
	 * @return integer
	 */
	public function getSize(): ?int {
		return $this->info->getSize();
	}

	/**
	 * Returns the cache's file id
	 *
	 * @return int
	 */
	public function getId(): ?int {
		return $this->info->getId();
	}

	/**
	 * @return string|null
	 */
	public function getFileId(): ?string {
		if ($this->info->getId()) {
			$instanceId = \OC_Util::getInstanceId();
			$id = sprintf('%08d', $this->info->getId());
			return $id . $instanceId;
		}

		return null;
	}

	/**
	 * @return integer
	 */
	public function getInternalFileId(): ?int {
		return $this->info->getId();
	}

	/**
	 * @param string|null $user
	 * @return int
	 */
	public function getSharePermissions(?string $user): int {

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

		if ($storage && $storage->instanceOfStorage(SharedStorage::class)) {
			/** @var SharedStorage $storage */
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
				$permissions |= Constants::PERMISSION_DELETE | Constants::PERMISSION_UPDATE;
			}
		}

		/*
		 * Files can't have create or delete permissions
		 */
		if ($this->info->getType() === FileInfo::TYPE_FILE) {
			$permissions &= ~(Constants::PERMISSION_CREATE | Constants::PERMISSION_DELETE);
		}

		return $permissions;
	}

	/**
	 * @param string|null $user
	 * @return string
	 */
	public function getNoteFromShare(?string $user): string {
		if ($user === null) {
			return '';
		}

		$types = [
			IShare::TYPE_USER,
			IShare::TYPE_GROUP,
			IShare::TYPE_CIRCLE,
			IShare::TYPE_ROOM
		];

		foreach ($types as $shareType) {
			$shares = $this->shareManager->getSharedWith($user, $shareType, $this, -1);
			foreach ($shares as $share) {
				$note = $share->getNote();
				if ($share->getShareOwner() !== $user && !empty($note)) {
					return $note;
				}
			}
		}

		return '';
	}

	/**
	 * @return string
	 */
	public function getDavPermissions(): string {
		$p = '';
		if ($this->info->isShared()) {
			$p .= 'S';
		}
		if ($this->info->isShareable()) {
			$p .= 'R';
		}
		if ($this->info->isMounted()) {
			$p .= 'M';
		}
		if ($this->info->isReadable()) {
			$p .= 'G';
		}
		if ($this->info->isDeletable()) {
			$p .= 'D';
		}
		if ($this->info->isUpdateable()) {
			$p .= 'NV'; // Renameable, Moveable
		}
		if ($this->info->getType() === FileInfo::TYPE_FILE) {
			if ($this->info->isUpdateable()) {
				$p .= 'W';
			}
		} else {
			if ($this->info->isCreatable()) {
				$p .= 'CK';
			}
		}
		return $p;
	}

	public function getOwner(): IUser {
		return $this->info->getOwner();
	}

	/**
	 * @throws InvalidPath
	 */
	protected function verifyPath() {
		try {
			$fileName = basename($this->info->getPath());
			$this->fileView->verifyPath($this->path, $fileName);
		} catch (InvalidPathException $ex) {
			throw new InvalidPath($ex->getMessage());
		}
	}

	/**
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @throws LockedException
	 */
	public function acquireLock(int $type) {
		$this->fileView->lockFile($this->path, $type);
	}

	/**
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @throws LockedException
	 */
	public function releaseLock(int $type) {
		$this->fileView->unlockFile($this->path, $type);
	}

	/**
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @throws LockedException
	 */
	public function changeLock(int $type) {
		$this->fileView->changeLock($this->path, $type);
	}

	public function getFileInfo(): ?FileInfo {
		return $this->info;
	}

	protected function sanitizeMtime($mtimeFromRequest): int {
		return MtimeSanitizer::sanitizeMtime($mtimeFromRequest);
	}
}
