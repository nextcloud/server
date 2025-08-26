<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Versions;

use OCA\Files_Versions\Db\VersionEntity;
use OCA\Files_Versions\Events\VersionCreatedEvent;
use OCA\Files_Versions\Events\VersionRestoredEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\Files\Lock\ILock;
use OCP\Files\Lock\ILockManager;
use OCP\Files\Lock\LockContext;
use OCP\Files\Node;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\Lock\ManuallyLockedException;
use OCP\Server;

class VersionManager implements IVersionManager, IDeletableVersionBackend, INeedSyncVersionBackend, IMetadataVersionBackend {

	/** @var (IVersionBackend[])[] */
	private $backends = [];

	public function __construct(
		private IEventDispatcher $dispatcher,
	) {
	}

	public function registerBackend(string $storageType, IVersionBackend $backend) {
		if (!isset($this->backends[$storageType])) {
			$this->backends[$storageType] = [];
		}
		$this->backends[$storageType][] = $backend;
	}

	/**
	 * @return (IVersionBackend[])[]
	 */
	private function getBackends(): array {
		return $this->backends;
	}

	/**
	 * @param IStorage $storage
	 * @return IVersionBackend
	 * @throws BackendNotFoundException
	 */
	public function getBackendForStorage(IStorage $storage): IVersionBackend {
		$fullType = get_class($storage);
		$backends = $this->getBackends();

		$foundType = '';
		$foundBackend = null;

		foreach ($backends as $type => $backendsForType) {
			if (
				$storage->instanceOfStorage($type)
				&& ($foundType === '' || is_subclass_of($type, $foundType))
			) {
				foreach ($backendsForType as $backend) {
					/** @var IVersionBackend $backend */
					if ($backend->useBackendForStorage($storage)) {
						$foundBackend = $backend;
						$foundType = $type;
					}
				}
			}
		}

		if ($foundType === '' || $foundBackend === null) {
			throw new BackendNotFoundException("Version backend for $fullType not found");
		} else {
			return $foundBackend;
		}
	}

	public function getVersionsForFile(IUser $user, FileInfo $file): array {
		$backend = $this->getBackendForStorage($file->getStorage());
		return $backend->getVersionsForFile($user, $file);
	}

	public function createVersion(IUser $user, FileInfo $file) {
		$backend = $this->getBackendForStorage($file->getStorage());
		$backend->createVersion($user, $file);
	}

	public function rollback(IVersion $version) {
		$backend = $version->getBackend();
		$result = self::handleAppLocks(fn (): ?bool => $backend->rollback($version));
		// rollback doesn't have a return type yet and some implementations don't return anything
		if ($result === null || $result === true) {
			$this->dispatcher->dispatchTyped(new VersionRestoredEvent($version));
		}
		return $result;
	}

	public function read(IVersion $version) {
		$backend = $version->getBackend();
		return $backend->read($version);
	}

	public function getVersionFile(IUser $user, FileInfo $sourceFile, $revision): File {
		$backend = $this->getBackendForStorage($sourceFile->getStorage());
		return $backend->getVersionFile($user, $sourceFile, $revision);
	}

	public function getRevision(Node $node): int {
		$backend = $this->getBackendForStorage($node->getStorage());
		return $backend->getRevision($node);
	}

	public function useBackendForStorage(IStorage $storage): bool {
		return false;
	}

	public function deleteVersion(IVersion $version): void {
		$backend = $version->getBackend();
		if ($backend instanceof IDeletableVersionBackend) {
			$backend->deleteVersion($version);
		}
	}

	public function createVersionEntity(File $file): void {
		$backend = $this->getBackendForStorage($file->getStorage());
		if ($backend instanceof INeedSyncVersionBackend) {
			$versionEntity = $backend->createVersionEntity($file);

			if ($versionEntity instanceof VersionEntity) {
				foreach ($backend->getVersionsForFile($file->getOwner(), $file) as $version) {
					if ($version->getRevisionId() === $versionEntity->getTimestamp()) {
						$this->dispatcher->dispatchTyped(new VersionCreatedEvent($file, $version));
						break;
					}
				}
			}
		}
	}

	public function updateVersionEntity(File $sourceFile, int $revision, array $properties): void {
		$backend = $this->getBackendForStorage($sourceFile->getStorage());
		if ($backend instanceof INeedSyncVersionBackend) {
			$backend->updateVersionEntity($sourceFile, $revision, $properties);
		}
	}

	public function deleteVersionsEntity(File $file): void {
		$backend = $this->getBackendForStorage($file->getStorage());
		if ($backend instanceof INeedSyncVersionBackend) {
			$backend->deleteVersionsEntity($file);
		}
	}

	public function setMetadataValue(Node $node, int $revision, string $key, string $value): void {
		$backend = $this->getBackendForStorage($node->getStorage());
		if ($backend instanceof IMetadataVersionBackend) {
			$backend->setMetadataValue($node, $revision, $key, $value);
		}
	}

	/**
	 * Catch ManuallyLockedException and retry in app context if possible.
	 *
	 * Allow users to go back to old versions via the versions tab in the sidebar
	 * even when the file is opened in the viewer next to it.
	 *
	 * Context: If a file is currently opened for editing
	 * the files_lock app will throw ManuallyLockedExceptions.
	 * This prevented the user from rolling an opened file back to a previous version.
	 *
	 * Text and Richdocuments can handle changes of open files.
	 * So we execute the rollback under their lock context
	 * to let them handle the conflict.
	 *
	 * @param callable $callback function to run with app locks handled
	 * @return bool|null
	 * @throws ManuallyLockedException
	 *
	 */
	private static function handleAppLocks(callable $callback): ?bool {
		try {
			return $callback();
		} catch (ManuallyLockedException $e) {
			$owner = (string)$e->getOwner();
			$appsThatHandleUpdates = ['text', 'richdocuments'];
			if (!in_array($owner, $appsThatHandleUpdates)) {
				throw $e;
			}
			// The LockWrapper in the files_lock app only compares the lock type and owner
			// when checking the lock against the current scope.
			// So we do not need to get the actual node here
			// and use the root node instead.
			$root = Server::get(IRootFolder::class);
			$lockContext = new LockContext($root, ILock::TYPE_APP, $owner);
			$lockManager = Server::get(ILockManager::class);
			$result = null;
			$lockManager->runInScope($lockContext, function () use ($callback, &$result): void {
				$result = $callback();
			});
			return $result;
		}
	}
}
