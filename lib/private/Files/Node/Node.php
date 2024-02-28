<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OC\Files\Node;

use OC\Files\Filesystem;
use OC\Files\Mount\MoveableMount;
use OC\Files\Utils\PathHelper;
use OCP\EventDispatcher\GenericEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\FileInfo;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\Node as INode;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Lock\LockedException;
use OCP\PreConditionNotMetException;

// FIXME: this class really should be abstract (+1)
class Node implements INode {
	/**
	 * @var \OC\Files\View $view
	 */
	protected $view;

	protected IRootFolder $root;

	/**
	 * @var string $path Absolute path to the node (e.g. /admin/files/folder/file)
	 */
	protected $path;

	protected ?FileInfo $fileInfo;

	protected ?INode $parent;

	private bool $infoHasSubMountsIncluded;

	/**
	 * @param \OC\Files\View $view
	 * @param \OCP\Files\IRootFolder $root
	 * @param string $path
	 * @param FileInfo $fileInfo
	 */
	public function __construct(IRootFolder $root, $view, $path, $fileInfo = null, ?INode $parent = null, bool $infoHasSubMountsIncluded = true) {
		if (Filesystem::normalizePath($view->getRoot()) !== '/') {
			throw new PreConditionNotMetException('The view passed to the node should not have any fake root set');
		}
		$this->view = $view;
		$this->root = $root;
		$this->path = $path;
		$this->fileInfo = $fileInfo;
		$this->parent = $parent;
		$this->infoHasSubMountsIncluded = $infoHasSubMountsIncluded;
	}

	/**
	 * Creates a Node of the same type that represents a non-existing path
	 *
	 * @param string $path path
	 * @return Node non-existing node
	 * @throws \Exception
	 */
	protected function createNonExistingNode($path) {
		throw new \Exception('Must be implemented by subclasses');
	}

	/**
	 * Returns the matching file info
	 *
	 * @return FileInfo
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getFileInfo(bool $includeMountPoint = true) {
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
		return $this->fileInfo;
	}

	/**
	 * @param string[] $hooks
	 */
	protected function sendHooks($hooks, array $args = null) {
		$args = !empty($args) ? $args : [$this];
		/** @var IEventDispatcher $dispatcher */
		$dispatcher = \OC::$server->get(IEventDispatcher::class);
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
	 * @param int $permissions
	 * @return bool
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	protected function checkPermissions($permissions) {
		return ($this->getPermissions() & $permissions) === $permissions;
	}

	public function delete() {
	}

	/**
	 * @param int $mtime
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function touch($mtime = null) {
		if ($this->checkPermissions(\OCP\Constants::PERMISSION_UPDATE)) {
			$this->sendHooks(['preTouch']);
			$this->view->touch($this->path, $mtime);
			$this->sendHooks(['postTouch']);
			if ($this->fileInfo) {
				if (is_null($mtime)) {
					$mtime = time();
				}
				$this->fileInfo['mtime'] = $mtime;
			}
		} else {
			throw new NotPermittedException();
		}
	}

	public function getStorage() {
		$storage = $this->getMountPoint()->getStorage();
		if (!$storage) {
			throw new \Exception("No storage for node");
		}
		return $storage;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getInternalPath() {
		return $this->getFileInfo(false)->getInternalPath();
	}

	/**
	 * @return int
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getId() {
		return $this->getFileInfo(false)->getId() ?? -1;
	}

	/**
	 * @return array
	 */
	public function stat() {
		return $this->view->stat($this->path);
	}

	/**
	 * @return int
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getMTime() {
		return $this->getFileInfo()->getMTime();
	}

	/**
	 * @param bool $includeMounts
	 * @return int|float
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getSize($includeMounts = true): int|float {
		return $this->getFileInfo()->getSize($includeMounts);
	}

	/**
	 * @return string
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getEtag() {
		return $this->getFileInfo()->getEtag();
	}

	/**
	 * @return int
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getPermissions() {
		return $this->getFileInfo(false)->getPermissions();
	}

	/**
	 * @return bool
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function isReadable() {
		return $this->getFileInfo(false)->isReadable();
	}

	/**
	 * @return bool
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function isUpdateable() {
		return $this->getFileInfo(false)->isUpdateable();
	}

	/**
	 * @return bool
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function isDeletable() {
		return $this->getFileInfo(false)->isDeletable();
	}

	/**
	 * @return bool
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function isShareable() {
		return $this->getFileInfo(false)->isShareable();
	}

	/**
	 * @return bool
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function isCreatable() {
		return $this->getFileInfo(false)->isCreatable();
	}

	public function getParent(): INode|IRootFolder {
		if ($this->parent === null) {
			$newPath = dirname($this->path);
			if ($newPath === '' || $newPath === '.' || $newPath === '/') {
				return $this->root;
			}

			// Manually fetch the parent if the current node doesn't have a file info yet
			try {
				$fileInfo = $this->getFileInfo();
			} catch (NotFoundException) {
				$this->parent = $this->root->get($newPath);
				/** @var \OCP\Files\Folder $this->parent */
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

	/**
	 * @return string
	 */
	public function getName() {
		return basename($this->path);
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function normalizePath($path) {
		return PathHelper::normalizePath($path);
	}

	/**
	 * check if the requested path is valid
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isValidPath($path) {
		return Filesystem::isValidPath($path);
	}

	public function isMounted() {
		return $this->getFileInfo(false)->isMounted();
	}

	public function isShared() {
		return $this->getFileInfo(false)->isShared();
	}

	public function getMimeType() {
		return $this->getFileInfo(false)->getMimetype();
	}

	public function getMimePart() {
		return $this->getFileInfo(false)->getMimePart();
	}

	public function getType() {
		return $this->getFileInfo(false)->getType();
	}

	public function isEncrypted() {
		return $this->getFileInfo(false)->isEncrypted();
	}

	public function getMountPoint() {
		return $this->getFileInfo(false)->getMountPoint();
	}

	public function getOwner() {
		return $this->getFileInfo(false)->getOwner();
	}

	public function getChecksum() {
	}

	public function getExtension(): string {
		return $this->getFileInfo(false)->getExtension();
	}

	/**
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @throws LockedException
	 */
	public function lock($type) {
		$this->view->lockFile($this->path, $type);
	}

	/**
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @throws LockedException
	 */
	public function changeLock($type) {
		$this->view->changeLock($this->path, $type);
	}

	/**
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @throws LockedException
	 */
	public function unlock($type) {
		$this->view->unlockFile($this->path, $type);
	}

	/**
	 * @param string $targetPath
	 * @return INode
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws NotPermittedException if copy not allowed or failed
	 */
	public function copy($targetPath) {
		$targetPath = $this->normalizePath($targetPath);
		$parent = $this->root->get(dirname($targetPath));
		if ($parent instanceof Folder and $this->isValidPath($targetPath) and $parent->isCreatable()) {
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

	/**
	 * @param string $targetPath
	 * @return INode
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws NotPermittedException if move not allowed or failed
	 * @throws LockedException
	 */
	public function move($targetPath) {
		$targetPath = $this->normalizePath($targetPath);
		$parent = $this->root->get(dirname($targetPath));
		if (
			$parent instanceof Folder and
			$this->isValidPath($targetPath) and
			(
				$parent->isCreatable() ||
				($parent->getInternalPath() === '' && $parent->getMountPoint() instanceof MoveableMount)
			)
		) {
			$nonExisting = $this->createNonExistingNode($targetPath);
			$this->sendHooks(['preRename'], [$this, $nonExisting]);
			$this->sendHooks(['preWrite'], [$nonExisting]);
			if (!$this->view->rename($this->path, $targetPath)) {
				throw new NotPermittedException('Could not move ' . $this->path . ' to ' . $targetPath);
			}

			$mountPoint = $this->getMountPoint();
			if ($mountPoint) {
				// update the cached fileinfo with the new (internal) path
				/** @var \OC\Files\FileInfo $oldFileInfo */
				$oldFileInfo = $this->getFileInfo();
				$this->fileInfo = new \OC\Files\FileInfo($targetPath, $oldFileInfo->getStorage(), $mountPoint->getInternalPath($targetPath), $oldFileInfo->getData(), $mountPoint, $oldFileInfo->getOwner());
			}

			$targetNode = $this->root->get($targetPath);
			$this->sendHooks(['postRename'], [$this, $targetNode]);
			$this->sendHooks(['postWrite'], [$targetNode]);
			$this->path = $targetPath;
			return $targetNode;
		} else {
			throw new NotPermittedException('No permission to move to path ' . $targetPath);
		}
	}

	public function getCreationTime(): int {
		return $this->getFileInfo()->getCreationTime();
	}

	public function getUploadTime(): int {
		return $this->getFileInfo()->getUploadTime();
	}

	public function getParentId(): int {
		return $this->fileInfo->getParentId();
	}

	/**
	 * @inheritDoc
	 * @return array<string, int|string|bool|float|string[]|int[]>
	 */
	public function getMetadata(): array {
		return $this->fileInfo->getMetadata();
	}
}
