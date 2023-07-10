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
use OCA\Files_Sharing\SharedStorage;
use OCA\Files_Versions\Db\VersionEntity;
use OCA\Files_Versions\Db\VersionsMapper;
use OCA\Files_Versions\Storage;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\IUserManager;

class LegacyVersionsBackend implements IVersionBackend, INameableVersionBackend, IDeletableVersionBackend {
	private IRootFolder $rootFolder;
	private IUserManager $userManager;
	private VersionsMapper $versionsMapper;
	private IMimeTypeLoader $mimeTypeLoader;

	public function __construct(
		IRootFolder $rootFolder,
		IUserManager $userManager,
		VersionsMapper $versionsMapper,
		IMimeTypeLoader $mimeTypeLoader
	) {
		$this->rootFolder = $rootFolder;
		$this->userManager = $userManager;
		$this->versionsMapper = $versionsMapper;
		$this->mimeTypeLoader = $mimeTypeLoader;
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

		$versions = $this->getVersionsForFileFromDB($file, $user);

		if (count($versions) > 0) {
			return $versions;
		}

		// Insert the entry in the DB for the current version.
		$versionEntity = new VersionEntity();
		$versionEntity->setFileId($fileId);
		$versionEntity->setTimestamp($file->getMTime());
		$versionEntity->setSize($file->getSize());
		$versionEntity->setMimetype($this->mimeTypeLoader->getId($file->getMimetype()));
		$versionEntity->setMetadata([]);
		$this->versionsMapper->insert($versionEntity);

		// Insert entries in the DB for existing versions.
		$relativePath = $userFolder->getRelativePath($file->getPath());
		if ($relativePath === null) {
			throw new NotFoundException("Relative path not found for file $fileId (" . $file->getPath() . ')');
		}

		$versionsOnFS = Storage::getVersions($user->getUID(), $relativePath);
		foreach ($versionsOnFS as $version) {
			$versionEntity = new VersionEntity();
			$versionEntity->setFileId($fileId);
			$versionEntity->setTimestamp((int)$version['version']);
			$versionEntity->setSize((int)$version['size']);
			$versionEntity->setMimetype($this->mimeTypeLoader->getId($version['mimetype']));
			$versionEntity->setMetadata([]);
			$this->versionsMapper->insert($versionEntity);
		}

		return $this->getVersionsForFileFromDB($file, $user);
	}

	/**
	 * @return IVersion[]
	 */
	private function getVersionsForFileFromDB(FileInfo $file, IUser $user): array {
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());

		return array_map(
			fn (VersionEntity $versionEntity) => new Version(
				$versionEntity->getTimestamp(),
				$versionEntity->getTimestamp(),
				$file->getName(),
				$versionEntity->getSize(),
				$this->mimeTypeLoader->getMimetypeById($versionEntity->getMimetype()),
				$userFolder->getRelativePath($file->getPath()),
				$file,
				$this,
				$user,
				$versionEntity->getLabel(),
			),
			$this->versionsMapper->findAllVersionsForFileId($file->getId())
		);
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

	public function setVersionLabel(IVersion $version, string $label): void {
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
		Storage::deleteRevision($version->getVersionPath(), $version->getRevisionId());
		$versionEntity = $this->versionsMapper->findVersionForFileId(
			$version->getSourceFile()->getId(),
			$version->getTimestamp(),
		);
		$this->versionsMapper->delete($versionEntity);
	}
}
