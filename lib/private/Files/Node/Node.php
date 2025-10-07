<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Node;

use OC\Files\Filesystem;
use OC\Files\Mount\MoveableMount;
use OC\Files\Utils\PathHelper;
use OC\Files\View;
use OCP\EventDispatcher\GenericEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\FileInfo;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Node as INode;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\PreConditionNotMetException;
use OCP\Server;
use Override;

// FIXME: this class really should be abstract (+1)
class Node implements INode {
	/**
	 * @param string $path Absolute path to the node (e.g. /admin/files/folder/file)
	 * @throws PreConditionNotMetException
	 */
	public function __construct(
		protected IRootFolder $root,
		protected View $view,
		protected string $path,
		protected ?FileInfo $fileInfo = null,
		protected ?\OCP\Files\Folder $parent = null,
		protected bool $infoHasSubMountsIncluded = true,
	) {
		if (Filesystem::normalizePath($view->getRoot()) !== '/') {
			throw new PreConditionNotMetException('The view passed to the node should not have any fake root set');
		}
	}

	/**
	 * Creates a Node of the same type that represents a non-existing path.
	 *
	 * @throws \Exception
	 */
	protected function createNonExistingNode(string $path): INode {
		throw new \Exception('Must be implemented by subclasses');
	}

	/**
	 * Returns the matching file info.
	 *
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getFileInfo(bool $includeMountPoint = true): FileInfo {
		$fileInfo = $this->fileInfo;
		if (!$this->fileInfo) {
			if (!Filesystem::isValidPath($this->path)) {
				throw new InvalidPathException();
			}
			$fileInfo = $this->view->getFileInfo($this->path, $includeMountPoint);
			$this->infoHasSubMountsIncluded = $includeMountPoint;
			if ($fileInfo instanceof FileInfo) {
				$this->fileInfo = $fileInfo;
			} else {
				throw new NotFoundException();
			}
		} elseif ($includeMountPoint && !$this->infoHasSubMountsIncluded && $this instanceof Folder) {
			if ($this->fileInfo instanceof \OC\Files\FileInfo) {
				$this->view->addSubMounts($this->fileInfo);
			}
			$this->infoHasSubMountsIncluded = true;
		}
		/** @var FileInfo $fileInfo */
		return $fileInfo;
	}

	/**
	 * @param string[] $hooks
	 */
	protected function sendHooks(array $hooks, ?array $args = null): void {
		$args = !empty($args) ? $args : [$this];
		$dispatcher = Server::get(IEventDispatcher::class);
		foreach ($hooks as $hook) {
			if (method_exists($this->root, 'emit')) {
				$this->root->emit('\OC\Files', $hook, $args);
			}

			if (in_array($hook, ['preWrite', 'postWrite', 'preCreate', 'postCreate', 'preTouch', 'postTouch', 'preDelete', 'postDelete'], true)) {
				$event = new GenericEvent($args[0]);
			} else {
				$event = new GenericEvent($args);
			}

			$dispatcher->dispatch('\OCP\Files::' . $hook, $event);
		}
	}

	/**
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	protected function checkPermissions(int $permissions): bool {
		return ($this->getPermissions() & $permissions) === $permissions;
	}

	#[Override]
	public function delete(): void {
	}

	#[Override]
	public function touch(?int $mtime = null): void {
		if ($this->checkPermissions(\OCP\Constants::PERMISSION_UPDATE)) {
			$this->sendHooks(['preTouch']);
			$this->view->touch($this->path, $mtime);
			$this->sendHooks(['postTouch']);
			if ($this->fileInfo instanceof \OC\Files\FileInfo) {
				if (is_null($mtime)) {
					$mtime = time();
				}
				$this->fileInfo['mtime'] = $mtime;
			}
		} else {
			throw new NotPermittedException();
		}
	}

	#[Override]
	public function getStorage(): IStorage {
		$storage = $this->getMountPoint()->getStorage();
		if (!$storage) {
			throw new \Exception('No storage for node');
		}
		return $storage;
	}

	#[Override]
	public function getPath(): string {
		return $this->path;
	}

	/**
	 * @return string
	 */
	#[Override]
	public function getInternalPath(): string {
		return $this->getFileInfo(false)->getInternalPath();
	}

	/**
	 * @return int
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	#[Override]
	public function getId(): int {
		return $this->getFileInfo(false)->getId();
	}

	#[Override]
	public function stat(): array|false {
		return $this->view->stat($this->path);
	}

	#[Override]
	public function getMTime(): int {
		return $this->getFileInfo()->getMTime();
	}

	#[Override]
	public function getSize(bool $includeMounts = true): int|float {
		return $this->getFileInfo()->getSize($includeMounts);
	}

	#[Override]
	public function getEtag(): string {
		return $this->getFileInfo()->getEtag();
	}

	#[Override]
	public function getPermissions(): int {
		return $this->getFileInfo(false)->getPermissions();
	}

	#[Override]
	public function isReadable(): bool {
		return $this->getFileInfo(false)->isReadable();
	}

	#[Override]
	public function isUpdateable(): bool {
		return $this->getFileInfo(false)->isUpdateable();
	}

	#[Override]
	public function isDeletable(): bool {
		return $this->getFileInfo(false)->isDeletable();
	}

	#[Override]
	public function isShareable(): bool {
		return $this->getFileInfo(false)->isShareable();
	}

	#[Override]
	public function isCreatable(): bool {
		return $this->getFileInfo(false)->isCreatable();
	}

	#[Override]
	public function getParent(): \OCP\Files\Folder|IRootFolder {
		if ($this->parent === null) {
			$newPath = dirname($this->path);
			if ($newPath === '' || $newPath === '.' || $newPath === '/') {
				return $this->root;
			}

			// Manually fetch the parent if the current node doesn't have a file info yet
			try {
				$fileInfo = $this->getFileInfo();
			} catch (NotFoundException) {
				/** @var \OCP\Files\Folder $parent */
				$parent = $this->root->get($newPath);
				$this->parent = $parent;
				return $this->parent;
			}

			// gather the metadata we already know about our parent
			$parentData = [
				'path' => $newPath,
				'fileid' => $fileInfo->getParentId(),
			];

			// and create lazy folder with it instead of always querying
			$this->parent = new LazyFolder($this->root, function () use ($newPath) {
				return $this->root->get($newPath);
			}, $parentData);
		}

		return $this->parent;
	}

	#[Override]
	public function getName(): string {
		return basename($this->path);
	}

	protected function normalizePath(string $path): string {
		return PathHelper::normalizePath($path);
	}

	/**
	 * Check if the requested path is valid.
	 */
	public function isValidPath(string $path): bool {
		return Filesystem::isValidPath($path);
	}

	#[Override]
	public function isMounted(): bool {
		return $this->getFileInfo(false)->isMounted();
	}

	#[Override]
	public function isShared(): bool {
		return $this->getFileInfo(false)->isShared();
	}

	#[Override]
	public function getMimeType(): string {
		return $this->getFileInfo(false)->getMimetype();
	}

	#[Override]
	public function getMimePart(): string {
		return $this->getFileInfo(false)->getMimePart();
	}

	#[Override]
	public function getType(): string {
		return $this->getFileInfo(false)->getType();
	}

	#[Override]
	public function isEncrypted(): bool {
		return $this->getFileInfo(false)->isEncrypted();
	}

	#[Override]
	public function getMountPoint(): IMountPoint {
		return $this->getFileInfo(false)->getMountPoint();
	}

	#[Override]
	public function getOwner(): ?IUser {
		return $this->getFileInfo(false)->getOwner();
	}

	#[Override]
	public function getChecksum(): string {
		throw new \Exception('Must be implemented by subclasses');
	}

	#[Override]
	public function getExtension(): string {
		return $this->getFileInfo(false)->getExtension();
	}

	#[Override]
	public function lock(int $type): void {
		$this->view->lockFile($this->path, $type);
	}

	#[Override]
	public function changeLock(int $targetType): void {
		$this->view->changeLock($this->path, $targetType);
	}

	#[Override]
	public function unlock(int $type): void {
		$this->view->unlockFile($this->path, $type);
	}

	#[Override]
	public function copy(string $targetPath): INode {
		$targetPath = $this->normalizePath($targetPath);
		$parent = $this->root->get(dirname($targetPath));
		if ($parent instanceof Folder && $this->isValidPath($targetPath) && $parent->isCreatable()) {
			$nonExisting = $this->createNonExistingNode($targetPath);
			$this->sendHooks(['preCopy'], [$this, $nonExisting]);
			$this->sendHooks(['preWrite'], [$nonExisting]);
			if (!$this->view->copy($this->path, $targetPath)) {
				throw new NotPermittedException('Could not copy ' . $this->path . ' to ' . $targetPath);
			}
			$targetNode = $this->root->get($targetPath);
			$this->sendHooks(['postCopy'], [$this, $targetNode]);
			$this->sendHooks(['postWrite'], [$targetNode]);
			return $targetNode;
		} else {
			throw new NotPermittedException('No permission to copy to path ' . $targetPath);
		}
	}

	#[Override]
	public function move(string $targetPath): INode {
		$targetPath = $this->normalizePath($targetPath);

		$parent = $this->root->get(dirname($targetPath));
		if (
			($parent instanceof Folder)
			&& $this->isValidPath($targetPath)
			&& (
				$parent->isCreatable()
				|| (
					$parent->getInternalPath() === ''
					&& ($parent->getMountPoint() instanceof MoveableMount)
				)
			)
		) {
			$nonExisting = $this->createNonExistingNode($targetPath);
			$this->sendHooks(['preRename'], [$this, $nonExisting]);
			$this->sendHooks(['preWrite'], [$nonExisting]);
			if (!$this->view->rename($this->path, $targetPath)) {
				throw new NotPermittedException('Could not move ' . $this->path . ' to ' . $targetPath);
			}

			$mountPoint = $this->getMountPoint();
			// update the cached fileinfo with the new (internal) path
			/** @var \OC\Files\FileInfo $oldFileInfo */
			$oldFileInfo = $this->getFileInfo();
			$this->fileInfo = new \OC\Files\FileInfo($targetPath, $oldFileInfo->getStorage(), $mountPoint->getInternalPath($targetPath), $oldFileInfo->getData(), $mountPoint, $oldFileInfo->getOwner());

			$targetNode = $this->root->get($targetPath);
			$this->sendHooks(['postRename'], [$this, $targetNode]);
			$this->sendHooks(['postWrite'], [$targetNode]);
			$this->path = $targetPath;
			return $targetNode;
		} else {
			throw new NotPermittedException('No permission to move to path ' . $targetPath);
		}
	}

	#[Override]
	public function getCreationTime(): int {
		return $this->getFileInfo()->getCreationTime();
	}

	#[Override]
	public function getUploadTime(): int {
		return $this->getFileInfo()->getUploadTime();
	}

	#[Override]
	public function getParentId(): int {
		return $this->fileInfo->getParentId();
	}

	#[Override]
	public function getMetadata(): array {
		return $this->fileInfo->getMetadata();
	}
}
