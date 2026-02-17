<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use OC\Files\Mount\MoveableMount;
use OC\Files\Node\File;
use OC\Files\Node\Folder;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCP\Constants;
use OCP\Files\DavUtil;
use OCP\Files\FileInfo;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\ISharedStorage;
use OCP\Files\StorageNotAvailableException;
use OCP\IUser;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use OCP\PreConditionNotMetException;
use OCP\Server;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use RuntimeException;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\INode;

abstract class Node implements INode {
	/**
	 * The path to the current node
	 */
	protected string $path;

	protected FileInfo $info;

	protected IManager $shareManager;

	protected \OCP\Files\Node $node;

	/**
	 * Sets up the node, expects a full path name
	 * @throws PreConditionNotMetException
	 */
	public function __construct(
		protected View $fileView,
		FileInfo $info,
		?IManager $shareManager = null,
	) {
		$relativePath = $this->fileView->getRelativePath($info->getPath());
		if ($relativePath === null) {
			throw new RuntimeException('Failed to get relative path for ' . $info->getPath());
		}

		$this->path = $relativePath;
		$this->info = $info;
		$this->shareManager = $shareManager instanceof IManager ? $shareManager : Server::get(IManager::class);

		if ($info instanceof Folder || $info instanceof File) {
			$this->node = $info;
		} else {
			// The Node API assumes that the view passed doesn't have a fake root
			$rootView = Server::get(View::class);
			$root = Server::get(IRootFolder::class);
			if ($info->getType() === FileInfo::TYPE_FOLDER) {
				$this->node = new Folder($root, $rootView, $this->fileView->getAbsolutePath($this->path), $info);
			} else {
				$this->node = new File($root, $rootView, $this->fileView->getAbsolutePath($this->path), $info);
			}
		}
	}

	/**
	 * @throws Exception
	 * @throws PreConditionNotMetException
	 */
	protected function refreshInfo(): void {
		$info = $this->fileView->getFileInfo($this->path);
		if ($info === false) {
			throw new Exception('Failed to get fileinfo for ' . $this->path);
		}

		$this->info = $info;
		$root = Server::get(IRootFolder::class);
		$rootView = Server::get(View::class);
		if ($this->info->getType() === FileInfo::TYPE_FOLDER) {
			$this->node = new Folder($root, $rootView, $this->path, $this->info);
		} else {
			$this->node = new File($root, $rootView, $this->path, $this->info);
		}
	}

	/**
	 *  Returns the name of the node
	 */
	public function getName(): string {
		return $this->info->getName();
	}

	/**
	 * Returns the full path
	 */
	public function getPath(): string {
		return $this->path;
	}

	/**
	 * Renames the node
	 *
	 * @param string $name The new name
	 * @throws Exception
	 * @throws Forbidden
	 * @throws InvalidPath
	 * @throws PreConditionNotMetException
	 * @throws LockedException
	 */
	public function setName($name): void {
		// rename is only allowed if the delete privilege is granted
		// (basically rename is a copy with delete of the original node)
		if (!$this->info->isDeletable() && !($this->info->getMountPoint() instanceof MoveableMount && $this->info->getInternalPath() === '')) {
			throw new Forbidden();
		}

		/** @var string $parentPath */
		[$parentPath,] = \Sabre\Uri\split($this->path);
		/** @var string $newName */
		[, $newName] = \Sabre\Uri\split($name);
		$newPath = $parentPath . '/' . $newName;

		// verify path of the target
		$this->verifyPath($newPath);

		if ($this->fileView->rename($this->path, $newPath) === false) {
			throw new Exception('Failed to rename ' . $this->path . ' to ' . $newPath);
		}

		$this->path = $newPath;

		$this->refreshInfo();
	}

	/**
	 * Returns the last modification time, as a unix timestamp
	 *
	 * @return int timestamp as integer
	 */
	public function getLastModified(): int {
		return $this->info->getMtime();
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
	 */
	public function getETag(): string {
		return '"' . $this->info->getEtag() . '"';
	}

	/**
	 * Sets the ETag
	 *
	 * @return int file id of updated file or -1 on failure
	 */
	public function setETag(string $etag): int {
		return $this->fileView->putFileInfo($this->path, ['etag' => $etag]);
	}

	public function setCreationTime(int $time): int {
		return $this->fileView->putFileInfo($this->path, ['creation_time' => $time]);
	}

	/**
	 * Returns the size of the node, in bytes
	 *
	 * @psalm-suppress UnusedPsalmSuppress psalm:strict actually thinks there is no mismatch, idk lol
	 * @psalm-suppress ImplementedReturnTypeMismatch \Sabre\DAV\IFile::getSize signature does not support 32bit
	 */
	public function getSize(): int|float {
		return $this->info->getSize();
	}

	/**
	 * Returns the cache's file id
	 */
	public function getId(): ?int {
		return $this->info->getId();
	}

	public function getFileId(): ?string {
		$id = $this->info->getId();
		if ($id !== null) {
			return DavUtil::getDavFileId($id);
		}

		return null;
	}

	public function getInternalFileId(): ?int {
		return $this->info->getId();
	}

	public function getInternalPath(): string {
		return $this->info->getInternalPath();
	}

	public function getSharePermissions(?string $user): int {
		// check of we access a federated share
		if ($user !== null) {
			try {
				return $this->shareManager->getShareByToken($user)->getPermissions();
			} catch (ShareNotFound) {
				// ignore
			}
		}

		try {
			$storage = $this->info->getStorage();
		} catch (StorageNotAvailableException) {
			$storage = null;
		}

		if ($storage && $storage->instanceOfStorage(ISharedStorage::class)) {
			$permissions = $storage->getShare()->getPermissions();
		} else {
			$permissions = $this->info->getPermissions();
		}

		/*
		 * We can always share non moveable mount points with DELETE and UPDATE
		 * Eventually we need to do this properly
		 */
		$mountpoint = $this->info->getMountPoint();
		if (!($mountpoint instanceof MoveableMount)) {
			/**
			 * @psalm-suppress UnnecessaryVarAnnotation Rector doesn't trust the return type annotation
			 * @var string $mountpointpath
			 */
			$mountpointpath = $mountpoint->getMountPoint();
			if (str_ends_with($mountpointpath, '/')) {
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

	public function getShareAttributes(): array {
		try {
			$storage = $this->node->getStorage();
		} catch (NotFoundException) {
			return [];
		}

		$attributes = [];
		if ($storage->instanceOfStorage(ISharedStorage::class)) {
			$attributes = $storage->getShare()->getAttributes();
			if ($attributes === null) {
				return [];
			}

			return $attributes->toArray();
		}

		return $attributes;
	}

	public function getNoteFromShare(?string $user): ?string {
		try {
			$storage = $this->node->getStorage();
		} catch (NotFoundException) {
			return null;
		}

		if ($storage->instanceOfStorage(ISharedStorage::class)) {
			$share = $storage->getShare();
			if ($user === $share->getShareOwner()) {
				// Note is only for recipient not the owner
				return null;
			}

			return $share->getNote();
		}

		return null;
	}

	public function getDavPermissions(): string {
		return DavUtil::getDavPermissions($this->info);
	}

	public function getOwner(): ?IUser {
		return $this->info->getOwner();
	}

	/**
	 * @throws InvalidPath
	 */
	protected function verifyPath(?string $path = null): void {
		try {
			$path ??= $this->info->getPath();
			$this->fileView->verifyPath(
				dirname($path),
				basename($path),
			);
		} catch (InvalidPathException $invalidPathException) {
			throw new InvalidPath($invalidPathException->getMessage(), false, $invalidPathException);
		}
	}

	/**
	 * @param ILockingProvider::LOCK_* $type
	 * @throws LockedException
	 */
	public function acquireLock($type): void {
		$this->fileView->lockFile($this->path, $type);
	}

	/**
	 * @param ILockingProvider::LOCK_* $type
	 * @throws LockedException
	 */
	public function releaseLock($type): void {
		$this->fileView->unlockFile($this->path, $type);
	}

	/**
	 * @param ILockingProvider::LOCK_* $type
	 * @throws LockedException
	 */
	public function changeLock($type): void {
		$this->fileView->changeLock($this->path, $type);
	}

	public function getFileInfo(): FileInfo {
		return $this->info;
	}

	public function getNode(): \OCP\Files\Node {
		return $this->node;
	}

	protected function sanitizeMtime(string $mtimeFromRequest): int {
		return MtimeSanitizer::sanitizeMtime($mtimeFromRequest);
	}
}
