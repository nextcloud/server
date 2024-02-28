<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

use OC\Files\View;
use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\Files_Sharing\SharedStorage;
use OCA\Files_Versions\Storage;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;

class LegacyVersionsBackend implements IVersionBackend {
	/** @var IRootFolder */
	private $rootFolder;
	/** @var IUserManager */
	private $userManager;
	/** @var IUserSession */
	private $userSession;

	public function __construct(IRootFolder $rootFolder, IUserManager $userManager, IUserSession $userSession) {
		$this->rootFolder = $rootFolder;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
	}

	public function useBackendForStorage(IStorage $storage): bool {
		return true;
	}

	public function getVersionsForFile(IUser $user, FileInfo $file): array {
		$storage = $file->getStorage();
		if ($storage->instanceOfStorage(SharedStorage::class)) {
			$owner = $storage->getOwner('');
			$user = $this->userManager->get($owner);

			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			$nodes = $userFolder->getById($file->getId());
			$file = array_pop($nodes);

			if (!$file) {
				throw new NotFoundException("version file not found for share owner");
			}
		}

		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$nodes = $userFolder->getById($file->getId());
		$file2 = array_pop($nodes);
		$versions = Storage::getVersions($user->getUID(), $userFolder->getRelativePath($file2->getPath()));

		return array_map(function (array $data) use ($file, $user) {
			return new Version(
				(int)$data['version'],
				(int)$data['version'],
				$data['name'],
				(int)$data['size'],
				$data['mimetype'],
				$data['path'],
				$file,
				$this,
				$user
			);
		}, $versions);
	}

	public function createVersion(IUser $user, FileInfo $file) {
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$relativePath = $userFolder->getRelativePath($file->getPath());
		$userView = new View('/' . $user->getUID());
		// create all parent folders
		Storage::createMissingDirectories($relativePath, $userView);

		Storage::scheduleExpire($user->getUID(), $relativePath);

		// store a new version of a file
		$userView->copy('files/' . $relativePath, 'files_versions/' . $relativePath . '.v' . $file->getMtime());
		// ensure the file is scanned
		$userView->getFileInfo('files_versions/' . $relativePath . '.v' . $file->getMtime());
	}

	public function rollback(IVersion $version) {
		if (!$this->currentUserHasPermissions($version, \OCP\Constants::PERMISSION_UPDATE)) {
			throw new Forbidden('You cannot restore this version because you do not have update permissions on the source file.');
		}

		return Storage::rollback($version->getVersionPath(), $version->getRevisionId(), $version->getUser());
	}

	private function getVersionFolder(IUser $user): Folder {
		$userRoot = $this->rootFolder->getUserFolder($user->getUID())
			->getParent();
		try {
			/** @var Folder $folder */
			$folder = $userRoot->get('files_versions');
			return $folder;
		} catch (NotFoundException $e) {
			return $userRoot->newFolder('files_versions');
		}
	}

	public function read(IVersion $version) {
		$versions = $this->getVersionFolder($version->getUser());
		/** @var File $file */
		$file = $versions->get($version->getVersionPath() . '.v' . $version->getRevisionId());
		return $file->fopen('r');
	}

	public function getVersionFile(IUser $user, FileInfo $sourceFile, $revision): File {
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$versionFolder = $this->getVersionFolder($user);
		/** @var File $file */
		$file = $versionFolder->get($userFolder->getRelativePath($sourceFile->getPath()) . '.v' . $revision);
		return $file;
	}

	private function currentUserHasPermissions(IVersion $version, int $permissions): bool {
		$sourceFile = $version->getSourceFile();
		$currentUserId = $this->userSession->getUser()->getUID();

		if ($currentUserId === null) {
			throw new NotFoundException("No user logged in");
		}

		if ($sourceFile->getOwner()->getUID() !== $currentUserId) {
			$nodes = $this->rootFolder->getUserFolder($currentUserId)->getById($sourceFile->getId());
			$sourceFile = array_pop($nodes);
			if (!$sourceFile) {
				throw new NotFoundException("Version file not accessible by current user");
			}
		}

		return ($sourceFile->getPermissions() & $permissions) === $permissions;
	}
}
