<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Trashbin\Service;

use OCA\Files_Trashbin\Trashbin;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Server;
use OCP\Util;

/**
 * @psalm-type TrashFolderSpaceInfo array{available: int|float, used: int|float}
 */
class TrashFolderService {
	public function __construct(
		readonly private IRootFolder $rootFolder,
		readonly private IAppManager $appManager,
	) {
	}

	public function getTrashFolderRoot(IUser $user): false|Folder {
		$userRoot = $this->rootFolder->getUserFolder($user->getUID())->getParent();

		try {
			/** @var Folder $folder */
			$folder = $userRoot->get('files_trashbin');
			return $folder;
		} catch (NotFoundException) {
			return false;
		}
	}

	public function getTrashFolder(IUser $user): false|Folder {
		$rootTrashFolder = $this->getTrashFolderRoot($user);
		if (!$rootTrashFolder) {
			return false;
		}

		/** @var Folder $folder */
		try {
			/** @var Folder $folder */
			$folder = $rootTrashFolder->get('files');
			return $folder;
		} catch (NotFoundException) {
			return $rootTrashFolder->newFolder('files');
		}
	}

	/**
	 * Calculate remaining free space for trash bin
	 *
	 * @return int|float The available space
	 */
	public function getAvailableSpace(Folder $trashFolderRoot, IUser $user): int|float {
		$configuredTrashBinSize = TrashBin::getConfiguredTrashbinSize($user->getUID());
		$trashBinSize = $trashFolderRoot->getSize(false);
		if ($configuredTrashBinSize > -1) {
			return $configuredTrashBinSize - $trashBinSize;
		}

		$softQuota = true;
		$quota = $user->getQuota();
		if ($quota === null || $quota === 'none') {
			$quota = $this->rootFolder->getFreeSpace();
			$softQuota = false;
			// inf or unknown free space
			if ($quota < 0) {
				$quota = PHP_INT_MAX;
			}
		} else {
			$quota = Util::computerFileSize($quota);
			// invalid quota
			if ($quota === false) {
				$quota = PHP_INT_MAX;
			}
		}

		// calculate available space for trash bin
		// subtract size of files and current trash bin size from quota
		if ($softQuota) {
			$userFolder = $trashFolderRoot->getParent();
			if (is_null($userFolder)) {
				return 0;
			}
			$free = $quota - $userFolder->getSize(false); // remaining free space for user
			if ($free > 0) {
				$availableSpace = ($free * Trashbin::DEFAULTMAXSIZE / 100) - $trashBinSize; // how much space can be used for versions
			} else {
				$availableSpace = $free - $trashBinSize;
			}
		} else {
			$availableSpace = $quota;
		}

		return Util::numericToNumber($availableSpace);
	}

	public static function delete(Folder $trashFolder, Node $node, IUser $user, $timestamp = null) {
		$size = 0;

		if ($timestamp) {
			$query = Server::get(IDBConnection::class)->getQueryBuilder();
			$query->delete('files_trash')
				->where($query->expr()->eq('user', $query->createNamedParameter($user)))
				->andWhere($query->expr()->eq('id', $query->createNamedParameter($node->getName())))
				->andWhere($query->expr()->eq('timestamp', $query->createNamedParameter($timestamp)));
			$query->executeStatement();

			$file = Trashbin::getTrashFilename($node->getName(), $timestamp);
		} else {
			$file = $node->getName();
		}

		//$size += Trashbin::deleteVersions($view, $file, $node, $timestamp, $user);

		try {
			$node = $trashFolder->get($file);
		} catch (NotFoundException) {
			return $size;
		}

		if ($node instanceof Folder) {
			$size += Trashbin::calculateSize(new View('/' . $user . '/files_trashbin/files/' . $file));
		} elseif ($node instanceof File) {
			$size += $view->filesize('/files_trashbin/files/' . $file);
		}

		Trashbin::emitTrashbinPreDelete('/files_trashbin/files/' . $file);
		$node->delete();
		Trashbin::emitTrashbinPostDelete('/files_trashbin/files/' . $file);

		return $size;
	}

	/**
	 * @param string $file
	 * @param string $filename
	 * @param ?int $timestamp
	 */
	private function deleteVersions(Folder $trashFolderRoot, $fileName, Node $node, ?int $timestamp, IUser $user): int|float {
		$size = 0;
		if ($this->appManager->isEnabledForUser('files_versions')) {
			$trashFolderRoot->get('versions/' . $fileName);
			if ($view->is_dir('files_trashbin/versions/' . $file)) {
				$size += Trashbin::calculateSize(new View('/' . $user . '/files_trashbin/versions/' . $file));
				$view->unlink('files_trashbin/versions/' . $file);
			} elseif ($versions = self::getVersionsFromTrash($filename, $timestamp, $user)) {
				foreach ($versions as $v) {
					if ($timestamp) {
						$size += $view->filesize('/files_trashbin/versions/' . static::getTrashFilename($filename . '.v' . $v, $timestamp));
						$view->unlink('/files_trashbin/versions/' . static::getTrashFilename($filename . '.v' . $v, $timestamp));
					} else {
						$size += $view->filesize('/files_trashbin/versions/' . $filename . '.v' . $v);
						$view->unlink('/files_trashbin/versions/' . $filename . '.v' . $v);
					}
				}
			}
		}
		return $size;
	}
}
