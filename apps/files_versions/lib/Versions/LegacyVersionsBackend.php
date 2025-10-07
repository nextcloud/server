<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Versions\Versions;

use Exception;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\Files_Versions\Db\VersionEntity;
use OCA\Files_Versions\Db\VersionsMapper;
use OCA\Files_Versions\Storage;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\ISharedStorage;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class LegacyVersionsBackend implements IVersionBackend, IDeletableVersionBackend, INeedSyncVersionBackend, IMetadataVersionBackend, IVersionsImporterBackend {
	public function __construct(
		private IRootFolder $rootFolder,
		private IUserManager $userManager,
		private VersionsMapper $versionsMapper,
		private IMimeTypeLoader $mimeTypeLoader,
		private IUserSession $userSession,
		private LoggerInterface $logger,
	) {
	}

	public function useBackendForStorage(IStorage $storage): bool {
		return true;
	}

	public function getVersionsForFile(IUser $user, FileInfo $file): array {
		$storage = $file->getStorage();

		if ($storage->instanceOfStorage(ISharedStorage::class)) {
			$owner = $storage->getOwner('');
			if ($owner === false) {
				throw new NotFoundException('No owner for ' . $file->getPath());
			}

			$user = $this->userManager->get($owner);

			$fileId = $file->getId();
			if ($fileId === -1) {
				throw new NotFoundException("File not found ($fileId)");
			}

			if ($user === null) {
				throw new NotFoundException("User $owner not found for $fileId");
			}

			$userFolder = $this->rootFolder->getUserFolder($user->getUID());

			$file = $userFolder->getFirstNodeById($fileId);

			if (!$file) {
				throw new NotFoundException('version file not found for share owner');
			}
		} else {
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		}

		$fileId = $file->getId();
		if ($fileId === -1) {
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
				$versions['db']->getMetadata() ?? [],
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
		if (!$this->currentUserHasPermissions($version->getSourceFile(), Constants::PERMISSION_UPDATE)) {
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
			/** @var ISharedStorage $storage */
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

	public function getRevision(Node $node): int {
		return $node->getMTime();
	}

	public function deleteVersion(IVersion $version): void {
		if (!$this->currentUserHasPermissions($version->getSourceFile(), Constants::PERMISSION_DELETE)) {
			throw new Forbidden('You cannot delete this version because you do not have delete permissions on the source file.');
		}

		Storage::deleteRevision($version->getVersionPath(), $version->getRevisionId());
		$versionEntity = $this->versionsMapper->findVersionForFileId(
			$version->getSourceFile()->getId(),
			$version->getTimestamp(),
		);
		$this->versionsMapper->delete($versionEntity);
	}

	public function createVersionEntity(File $file): ?VersionEntity {
		$versionEntity = new VersionEntity();
		$versionEntity->setFileId($file->getId());
		$versionEntity->setTimestamp($file->getMTime());
		$versionEntity->setSize($file->getSize());
		$versionEntity->setMimetype($this->mimeTypeLoader->getId($file->getMimetype()));
		$versionEntity->setMetadata([]);

		$tries = 1;
		while ($tries < 5) {
			try {
				$this->versionsMapper->insert($versionEntity);
				return $versionEntity;
			} catch (\OCP\DB\Exception $e) {
				if (!in_array($e->getReason(), [
					\OCP\DB\Exception::REASON_CONSTRAINT_VIOLATION,
					\OCP\DB\Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION,
				])
				) {
					throw $e;
				}
				/* Conflict with another version, increase mtime and try again */
				$versionEntity->setTimestamp($versionEntity->getTimestamp() + 1);
				$tries++;
				$this->logger->warning('Constraint violation while inserting version, retrying with increased timestamp', ['exception' => $e]);
			}
		}

		return null;
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

	private function currentUserHasPermissions(FileInfo $sourceFile, int $permissions): bool {
		$currentUserId = $this->userSession->getUser()?->getUID();

		if ($currentUserId === null) {
			throw new NotFoundException('No user logged in');
		}

		if ($sourceFile->getOwner()?->getUID() === $currentUserId) {
			return ($sourceFile->getPermissions() & $permissions) === $permissions;
		}

		$nodes = $this->rootFolder->getUserFolder($currentUserId)->getById($sourceFile->getId());

		if (count($nodes) === 0) {
			throw new NotFoundException('Version file not accessible by current user');
		}

		foreach ($nodes as $node) {
			if (($node->getPermissions() & $permissions) === $permissions) {
				return true;
			}
		}

		return false;
	}

	public function setMetadataValue(Node $node, int $revision, string $key, string $value): void {
		if (!$this->currentUserHasPermissions($node, Constants::PERMISSION_UPDATE)) {
			throw new Forbidden('You cannot update the version\'s metadata because you do not have update permissions on the source file.');
		}

		$versionEntity = $this->versionsMapper->findVersionForFileId($node->getId(), $revision);

		$versionEntity->setMetadataValue($key, $value);
		$this->versionsMapper->update($versionEntity);
	}


	/**
	 * @inheritdoc
	 */
	public function importVersionsForFile(IUser $user, Node $source, Node $target, array $versions): void {
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$relativePath = $userFolder->getRelativePath($target->getPath());

		if ($relativePath === null) {
			throw new \Exception('Target does not have a relative path' . $target->getPath());
		}

		$userView = new View('/' . $user->getUID());
		// create all parent folders
		Storage::createMissingDirectories($relativePath, $userView);
		Storage::scheduleExpire($user->getUID(), $relativePath);

		foreach ($versions as $version) {
			// 1. Import the file in its new location.
			// Nothing to do for the current version.
			if ($version->getTimestamp() !== $source->getMTime()) {
				$backend = $version->getBackend();
				$versionFile = $backend->getVersionFile($user, $source, $version->getRevisionId());
				$newVersionPath = 'files_versions/' . $relativePath . '.v' . $version->getTimestamp();

				$versionContent = $versionFile->fopen('r');
				if ($versionContent === false) {
					$this->logger->warning('Fail to open version file.', ['source' => $source, 'version' => $version, 'versionFile' => $versionFile]);
					continue;
				}

				$userView->file_put_contents($newVersionPath, $versionContent);
				// ensure the file is scanned
				$userView->getFileInfo($newVersionPath);
			}

			// 2. Create the entity in the database
			$versionEntity = new VersionEntity();
			$versionEntity->setFileId($target->getId());
			$versionEntity->setTimestamp($version->getTimestamp());
			$versionEntity->setSize($version->getSize());
			$versionEntity->setMimetype($this->mimeTypeLoader->getId($version->getMimetype()));
			if ($version instanceof IMetadataVersion) {
				$versionEntity->setMetadata($version->getMetadata());
			}
			$this->versionsMapper->insert($versionEntity);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function clearVersionsForFile(IUser $user, Node $source, Node $target): void {
		$userId = $user->getUID();
		$userFolder = $this->rootFolder->getUserFolder($userId);

		$relativePath = $userFolder->getRelativePath($source->getPath());
		if ($relativePath === null) {
			throw new Exception('Relative path not found for node with path: ' . $source->getPath());
		}

		$versionFolder = $this->getVersionFolder($user);

		$versions = Storage::getVersions($userId, $relativePath);
		foreach ($versions as $version) {
			$versionFolder->get($version['path'] . '.v' . (int)$version['version'])->delete();
		}

		$this->versionsMapper->deleteAllVersionsForFileId($target->getId());
	}
}
