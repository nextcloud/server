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
use OCA\Files_Sharing\ISharedStorage;
use OCA\Files_Sharing\SharedStorage;
use OCA\Files_Versions\Db\VersionEntity;
use OCA\Files_Versions\Db\VersionsMapper;
use OCA\Files_Versions\Storage;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;

class LegacyVersionsBackend implements IVersionBackend, INameableVersionBackend, IDeletableVersionBackend, INeedSyncVersionBackend {
	private IRootFolder $rootFolder;
	private IUserManager $userManager;
	private VersionsMapper $versionsMapper;
	private IMimeTypeLoader $mimeTypeLoader;
	private IUserSession $userSession;

	public function __construct(
		IRootFolder $rootFolder,
		IUserManager $userManager,
		VersionsMapper $versionsMapper,
		IMimeTypeLoader $mimeTypeLoader,
		IUserSession $userSession,
	) {
		$this->rootFolder = $rootFolder;
		$this->userManager = $userManager;
		$this->versionsMapper = $versionsMapper;
		$this->mimeTypeLoader = $mimeTypeLoader;
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

			$fileId = $file->getId();
			if ($fileId === null) {
				throw new NotFoundException("File not found ($fileId)");
			}

			if ($user === null) {
				throw new NotFoundException("User $owner not found for $fileId");
			}

			$userFolder = $this->rootFolder->getUserFolder($user->getUID());

			$nodes = $userFolder->getById($fileId);
			$file = array_pop($nodes);

			if (!$file) {
				throw new NotFoundException("version file not found for share owner");
			}
		} else {
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		}

		$fileId = $file->getId();
		if ($fileId === null) {
			throw new NotFoundException("File not found ($fileId)");
		}

		// Insert entries in the DB for existing versions.
		$relativePath = $userFolder->getRelativePath($file->getPath());
		if ($relativePath === null) {
			throw new NotFoundException("Relative path not found for file $fileId (" . $file->getPath() . ')');
		}

		$currentVersion = [
			'version' => (string)$file->getMtime(),
			'size' => $file->getSize(),
			'mimetype' => $file->getMimetype(),
		];

		$versionsInDB = $this->versionsMapper->findAllVersionsForFileId($file->getId());
		/** @var array<int, array> */
		$versionsInFS = array_values(Storage::getVersions($user->getUID(), $relativePath));

		/** @var array<int, array{db: ?VersionEntity, fs: ?mixed}> */
		$groupedVersions = [];
		$davVersions = [];

		foreach ($versionsInDB as $version) {
			$revisionId = $version->getTimestamp();
			$groupedVersions[$revisionId] = $groupedVersions[$revisionId] ?? [];
			$groupedVersions[$revisionId]['db'] = $version;
		}

		foreach ([$currentVersion, ...$versionsInFS] as $version) {
			$revisionId = $version['version'];
			$groupedVersions[$revisionId] = $groupedVersions[$revisionId] ?? [];
			$groupedVersions[$revisionId]['fs'] = $version;
		}

		/** @var array<string, array{db: ?VersionEntity, fs: ?mixed}> $groupedVersions */
		foreach ($groupedVersions as $versions) {
			if (empty($versions['db']) && !empty($versions['fs'])) {
				$versions['db'] = new VersionEntity();
				$versions['db']->setFileId($fileId);
				$versions['db']->setTimestamp((int)$versions['fs']['version']);
				$versions['db']->setSize((int)$versions['fs']['size']);
				$versions['db']->setMimetype($this->mimeTypeLoader->getId($versions['fs']['mimetype']));
				$versions['db']->setMetadata([]);
				$this->versionsMapper->insert($versions['db']);
			} elseif (!empty($versions['db']) && empty($versions['fs'])) {
				$this->versionsMapper->delete($versions['db']);
				continue;
			}

			$version = new Version(
				$versions['db']->getTimestamp(),
				$versions['db']->getTimestamp(),
				$file->getName(),
				$versions['db']->getSize(),
				$this->mimeTypeLoader->getMimetypeById($versions['db']->getMimetype()),
				$userFolder->getRelativePath($file->getPath()),
				$file,
				$this,
				$user,
				$versions['db']->getLabel(),
			);

			array_push($davVersions, $version);
		}

		return $davVersions;
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
		$owner = $sourceFile->getOwner();
		$storage = $sourceFile->getStorage();

		// Shared files have their versions in the owners root folder so we need to obtain them from there
		if ($storage->instanceOfStorage(ISharedStorage::class) && $owner) {
			/** @var SharedStorage $storage */
			$userFolder = $this->rootFolder->getUserFolder($owner->getUID());
			$user = $owner;
			$ownerPathInStorage = $sourceFile->getInternalPath();
			$sourceFile = $storage->getShare()->getNode();
			if ($sourceFile instanceof Folder) {
				$sourceFile = $sourceFile->get($ownerPathInStorage);
			}
		}

		$versionFolder = $this->getVersionFolder($user);
		/** @var File $file */
		$file = $versionFolder->get($userFolder->getRelativePath($sourceFile->getPath()) . '.v' . $revision);
		return $file;
	}

	public function setVersionLabel(IVersion $version, string $label): void {
		if (!$this->currentUserHasPermissions($version, \OCP\Constants::PERMISSION_UPDATE)) {
			throw new Forbidden('You cannot label this version because you do not have update permissions on the source file.');
		}

		$versionEntity = $this->versionsMapper->findVersionForFileId(
			$version->getSourceFile()->getId(),
			$version->getTimestamp(),
		);
		if (trim($label) === '') {
			$label = null;
		}
		$versionEntity->setLabel($label ?? '');
		$this->versionsMapper->update($versionEntity);
	}

	public function deleteVersion(IVersion $version): void {
		if (!$this->currentUserHasPermissions($version, \OCP\Constants::PERMISSION_DELETE)) {
			throw new Forbidden('You cannot delete this version because you do not have delete permissions on the source file.');
		}

		Storage::deleteRevision($version->getVersionPath(), $version->getRevisionId());
		$versionEntity = $this->versionsMapper->findVersionForFileId(
			$version->getSourceFile()->getId(),
			$version->getTimestamp(),
		);
		$this->versionsMapper->delete($versionEntity);
	}

	public function createVersionEntity(File $file): void {
		$versionEntity = new VersionEntity();
		$versionEntity->setFileId($file->getId());
		$versionEntity->setTimestamp($file->getMTime());
		$versionEntity->setSize($file->getSize());
		$versionEntity->setMimetype($this->mimeTypeLoader->getId($file->getMimetype()));
		$versionEntity->setMetadata([]);
		$this->versionsMapper->insert($versionEntity);
	}

	public function updateVersionEntity(File $sourceFile, int $revision, array $properties): void {
		$versionEntity = $this->versionsMapper->findVersionForFileId($sourceFile->getId(), $revision);

		if (isset($properties['timestamp'])) {
			$versionEntity->setTimestamp($properties['timestamp']);
		}

		if (isset($properties['size'])) {
			$versionEntity->setSize($properties['size']);
		}

		if (isset($properties['mimetype'])) {
			$versionEntity->setMimetype($properties['mimetype']);
		}

		$this->versionsMapper->update($versionEntity);
	}

	public function deleteVersionsEntity(File $file): void {
		$this->versionsMapper->deleteAllVersionsForFileId($file->getId());
	}

	private function currentUserHasPermissions(IVersion $version, int $permissions): bool {
		$sourceFile = $version->getSourceFile();
		$currentUserId = $this->userSession->getUser()?->getUID();

		if ($currentUserId === null) {
			throw new NotFoundException("No user logged in");
		}

		if ($sourceFile->getOwner()?->getUID() !== $currentUserId) {
			$nodes = $this->rootFolder->getUserFolder($currentUserId)->getById($sourceFile->getId());
			$sourceFile = array_pop($nodes);
			if (!$sourceFile) {
				throw new NotFoundException("Version file not accessible by current user");
			}
		}

		return ($sourceFile->getPermissions() & $permissions) === $permissions;
	}
}
