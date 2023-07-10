<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Files_Versions\Versions;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\Files\Lock\ILock;
use OCP\Files\Lock\ILockManager;
use OCP\Files\Lock\LockContext;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\Lock\ManuallyLockedException;

class VersionManager implements IVersionManager, INameableVersionBackend, IDeletableVersionBackend {
	/** @var (IVersionBackend[])[] */
	private $backends = [];

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
				$storage->instanceOfStorage($type) &&
				($foundType === '' || is_subclass_of($type, $foundType))
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
		$result = self::handleAppLocks(fn(): ?bool => $backend->rollback($version));
		// rollback doesn't have a return type yet and some implementations don't return anything
		if ($result === null || $result === true) {
			\OC_Hook::emit('\OCP\Versions', 'rollback', [
				'path' => $version->getVersionPath(),
				'revision' => $version->getRevisionId(),
				'node' => $version->getSourceFile(),
			]);
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

	public function useBackendForStorage(IStorage $storage): bool {
		return false;
	}

	public function setVersionLabel(IVersion $version, string $label): void {
		$backend = $this->getBackendForStorage($version->getSourceFile()->getStorage());
		if ($backend instanceof INameableVersionBackend) {
			$backend->setVersionLabel($version, $label);
		}
	}

	public function deleteVersion(IVersion $version): void {
		$backend = $this->getBackendForStorage($version->getSourceFile()->getStorage());
		if ($backend instanceof IDeletableVersionBackend) {
			$backend->deleteVersion($version);
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
			$owner = (string) $e->getOwner();
			$appsThatHandleUpdates = array("text", "richdocuments");
			if (!in_array($owner, $appsThatHandleUpdates)) {
				throw $e;
			}
			// The LockWrapper in the files_lock app only compares the lock type and owner
			// when checking the lock against the current scope.
			// So we do not need to get the actual node here
			// and use the root node instead.
			$root = \OC::$server->get(IRootFolder::class);
			$lockContext = new LockContext($root, ILock::TYPE_APP, $owner);
			$lockManager = \OC::$server->get(ILockManager::class);
			$result = null;
			$lockManager->runInScope($lockContext, function() use ($callback, &$result) {
				$result = $callback();
			});
			return $result;
		}
	}

}
