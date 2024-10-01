<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Trash;

use OCP\Files\Storage\IStorage;
use OCP\IUser;

class TrashManager implements ITrashManager {
	/** @var ITrashBackend[] */
	private $backends = [];

	private $trashPaused = false;

	public function registerBackend(string $storageType, ITrashBackend $backend) {
		$this->backends[$storageType] = $backend;
	}

	/**
	 * @return ITrashBackend[]
	 */
	private function getBackends(): array {
		return $this->backends;
	}

	public function listTrashRoot(IUser $user): array {
		$items = array_reduce($this->getBackends(), function (array $items, ITrashBackend $backend) use ($user) {
			return array_merge($items, $backend->listTrashRoot($user));
		}, []);
		usort($items, function (ITrashItem $a, ITrashItem $b) {
			return $b->getDeletedTime() - $a->getDeletedTime();
		});
		return $items;
	}

	private function getBackendForItem(ITrashItem $item) {
		return $item->getTrashBackend();
	}

	public function listTrashFolder(ITrashItem $folder): array {
		return $this->getBackendForItem($folder)->listTrashFolder($folder);
	}

	public function restoreItem(ITrashItem $item) {
		return $this->getBackendForItem($item)->restoreItem($item);
	}

	public function removeItem(ITrashItem $item) {
		$this->getBackendForItem($item)->removeItem($item);
	}

	/**
	 * @param IStorage $storage
	 * @return ITrashBackend
	 * @throws BackendNotFoundException
	 */
	public function getBackendForStorage(IStorage $storage): ITrashBackend {
		$fullType = get_class($storage);
		$foundType = array_reduce(array_keys($this->backends), function ($type, $registeredType) use ($storage) {
			if (
				$storage->instanceOfStorage($registeredType) &&
				($type === '' || is_subclass_of($registeredType, $type))
			) {
				return $registeredType;
			} else {
				return $type;
			}
		}, '');
		if ($foundType === '') {
			throw new BackendNotFoundException("Trash backend for $fullType not found");
		} else {
			return $this->backends[$foundType];
		}
	}

	public function moveToTrash(IStorage $storage, string $internalPath): bool {
		if ($this->trashPaused) {
			return false;
		}
		try {
			$backend = $this->getBackendForStorage($storage);
			$this->trashPaused = true;
			$result = $backend->moveToTrash($storage, $internalPath);
			$this->trashPaused = false;
			return $result;
		} catch (BackendNotFoundException $e) {
			return false;
		}
	}

	public function getTrashNodeById(IUser $user, int $fileId) {
		foreach ($this->backends as $backend) {
			$item = $backend->getTrashNodeById($user, $fileId);
			if ($item !== null) {
				return $item;
			}
		}
		return null;
	}

	public function pauseTrash() {
		$this->trashPaused = true;
	}

	public function resumeTrash() {
		$this->trashPaused = false;
	}
}
