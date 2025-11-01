<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Trashbin\Trash;

use OC\Files\Filesystem;
use OC\Files\Node\LazyFolder;
use OC\Files\View;
use OCA\Files_Trashbin\Helper;
use OCA\Files_Trashbin\Storage;
use OCA\Files_Trashbin\Trashbin;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorage;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;

class LegacyTrashBackend implements ITrashBackend {
	/** @var array */
	private $deletedFiles = [];

	public function __construct(
		private IRootFolder $rootFolder,
		private IUserManager $userManager,
	) {
	}

	/**
	 * @param array $items
	 * @param IUser $user
	 * @param ITrashItem $parent
	 * @return ITrashItem[]
	 */
	private function mapTrashItems(array $items, IUser $user, ?ITrashItem $parent = null): array {
		$parentTrashPath = ($parent instanceof ITrashItem) ? $parent->getTrashPath() : '';
		$isRoot = $parent === null;
		return array_map(function (FileInfo $file) use ($parent, $parentTrashPath, $isRoot, $user) {
			$originalLocation = $isRoot ? $file['extraData'] : $parent->getOriginalLocation() . '/' . $file->getName();
			if (!$originalLocation) {
				$originalLocation = $file->getName();
			}
			/** @psalm-suppress UndefinedInterfaceMethod */
			$deletedBy = $this->userManager->get($file['deletedBy']) ?? $parent?->getDeletedBy();
			$trashFilename = Trashbin::getTrashFilename($file->getName(), $file->getMtime());
			return new TrashItem(
				$this,
				$originalLocation,
				$file->getMTime(),
				$parentTrashPath . '/' . ($isRoot ? $trashFilename : $file->getName()),
				$file,
				$user,
				$deletedBy,
			);
		}, $items);
	}

	public function listTrashRoot(IUser $user): array {
		$entries = Helper::getTrashFiles('/', $user->getUID());
		return $this->mapTrashItems($entries, $user);
	}

	public function listTrashFolder(ITrashItem $folder): array {
		$user = $folder->getUser();
		$entries = Helper::getTrashFiles($folder->getTrashPath(), $user->getUID());
		return $this->mapTrashItems($entries, $user, $folder);
	}

	public function restoreItem(ITrashItem $item) {
		Trashbin::restore($item->getTrashPath(), $item->getName(), $item->isRootItem() ? $item->getDeletedTime() : null);
	}

	public function removeItem(ITrashItem $item) {
		$user = $item->getUser();
		if ($item->isRootItem()) {
			$path = substr($item->getTrashPath(), 0, -strlen('.d' . $item->getDeletedTime()));
			Trashbin::delete($path, $user->getUID(), $item->getDeletedTime());
		} else {
			Trashbin::delete($item->getTrashPath(), $user->getUID(), null);
		}
	}

	public function moveToTrash(IStorage $storage, string $internalPath): bool {
		if (!$storage instanceof Storage) {
			return false;
		}
		$normalized = Filesystem::normalizePath($storage->getMountPoint() . '/' . $internalPath, true, false, true);
		$view = Filesystem::getView();
		if (!isset($this->deletedFiles[$normalized]) && $view instanceof View) {
			$this->deletedFiles[$normalized] = $normalized;
			if ($filesPath = $view->getRelativePath($normalized)) {
				$filesPath = trim($filesPath, '/');
				$result = Trashbin::move2trash($filesPath);
			} else {
				$result = false;
			}
			unset($this->deletedFiles[$normalized]);
		} else {
			$result = false;
		}

		return $result;
	}

	public function getTrashNodeById(IUser $user, int $fileId) {
		try {
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			$trash = $userFolder->getParent()->get('files_trashbin/files');
			if ($trash instanceof Folder) {
				return $trash->getFirstNodeById($fileId);
			} else {
				return null;
			}
		} catch (NotFoundException $e) {
			return null;
		}
	}

	public function getCacheableRootsForUser(IUser $user): array {
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$connection = Server::get(IDBConnection::class);
		$qb = $connection->getQueryBuilder();
		$qb->select('fileid', 'storage')
			->from('filecache')
			->hintShardKey('storage', $userFolder->getMountPoint()->getNumericStorageId())
			->where($qb->expr()->eq('path_hash', $qb->createNamedParameter(md5('files_trashbin/files'))));
		$result = $qb->executeQuery()->fetchAll();

		if (count($result) === 0) {
			return [];
		}

		return [
			new LazyFolder(
				$this->rootFolder,
				fn () => $userFolder->getParent()->get('files_trashbin/files'),
				[
					'fileid' => $result[0]['fileid'],
					'mountpoint_numericStorageId' => $result[0]['storage'],
				]
			)
		];
	}
}
