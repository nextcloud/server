<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing;

use OC\Files\Cache\FileAccess;
use OC\Files\Filesystem;
use OC\Files\Mount\MountPoint;
use OCP\Constants;
use OCP\Files\Folder;
use OCP\Files\Mount\IMountManager;
use OCP\Server;
use OCP\Share\IShare;

class Updater {

	/**
	 * @param array $params
	 */
	public static function renameHook($params) {
		self::renameChildren($params['oldpath'], $params['newpath']);
		self::moveShareInOrOutOfShare($params['newpath']);
	}

	/**
	 * Fix for https://github.com/owncloud/core/issues/20769
	 *
	 * The owner is allowed to move their files (if they are shared) into a receiving folder
	 * In this case we need to update the parent of the moved share. Since they are
	 * effectively handing over ownership of the file the rest of the code needs to know
	 * they need to build up the reshare tree.
	 *
	 * @param string $path
	 */
	private static function moveShareInOrOutOfShare($path): void {
		$userFolder = \OC::$server->getUserFolder();

		// If the user folder can't be constructed (e.g. link share) just return.
		if ($userFolder === null) {
			return;
		}
		$user = $userFolder->getOwner();
		if (!$user) {
			throw new \Exception('user folder has no owner');
		}

		$src = $userFolder->get($path);

		$shareManager = Server::get(\OCP\Share\IManager::class);

		// We intentionally include invalid shares, as they have been automatically invalidated due to the node no longer
		// being accessible for the user. Only in this case where we adjust the share after it was moved we want to ignore
		// this to be able to still adjust it.

		// FIXME: should CIRCLES be included here ??
		$shares = $shareManager->getSharesBy($user->getUID(), IShare::TYPE_USER, $src, false, -1, onlyValid: false);
		$shares = array_merge($shares, $shareManager->getSharesBy($user->getUID(), IShare::TYPE_GROUP, $src, false, -1, onlyValid: false));
		$shares = array_merge($shares, $shareManager->getSharesBy($user->getUID(), IShare::TYPE_ROOM, $src, false, -1, onlyValid: false));

		if ($src instanceof Folder) {
			$cacheAccess = Server::get(FileAccess::class);

			$sourceStorageId = $src->getStorage()->getCache()->getNumericStorageId();
			$sourceInternalPath = $src->getInternalPath();
			$subShares = array_merge(
				$shareManager->getSharesBy($user->getUID(), IShare::TYPE_USER, onlyValid: false),
				$shareManager->getSharesBy($user->getUID(), IShare::TYPE_GROUP, onlyValid: false),
				$shareManager->getSharesBy($user->getUID(), IShare::TYPE_ROOM, onlyValid: false),
			);
			$shareSourceIds = array_map(fn (IShare $share) => $share->getNodeId(), $subShares);
			$shareSources = $cacheAccess->getByFileIdsInStorage($shareSourceIds, $sourceStorageId);
			foreach ($subShares as $subShare) {
				$shareCacheEntry = $shareSources[$subShare->getNodeId()] ?? null;
				if (
					$shareCacheEntry
					&& str_starts_with($shareCacheEntry->getPath(), $sourceInternalPath . '/')
				) {
					$shares[] = $subShare;
				}
			}
		}

		// If the path we move is not a share we don't care
		if (empty($shares)) {
			return;
		}

		// Check if the destination is inside a share
		$mountManager = Server::get(IMountManager::class);
		$dstMount = $mountManager->find($src->getPath());

		//Ownership is moved over
		foreach ($shares as $share) {
			if (
				$share->getShareType() !== IShare::TYPE_USER
				&& $share->getShareType() !== IShare::TYPE_GROUP
				&& $share->getShareType() !== IShare::TYPE_ROOM
			) {
				continue;
			}

			if ($dstMount instanceof SharedMount) {
				if (!($dstMount->getShare()->getPermissions() & Constants::PERMISSION_SHARE)) {
					$shareManager->deleteShare($share);
					continue;
				}
				$newOwner = $dstMount->getShare()->getShareOwner();
				$newPermissions = $share->getPermissions() & $dstMount->getShare()->getPermissions();
			} else {
				$newOwner = $userFolder->getOwner()->getUID();
				$newPermissions = $share->getPermissions();
			}

			$share->setShareOwner($newOwner);
			$share->setPermissions($newPermissions);
			$shareManager->updateShare($share, onlyValid: false);
		}
	}

	/**
	 * rename mount point from the children if the parent was renamed
	 *
	 * @param string $oldPath old path relative to data/user/files
	 * @param string $newPath new path relative to data/user/files
	 */
	private static function renameChildren($oldPath, $newPath) {
		$absNewPath = Filesystem::normalizePath('/' . \OC_User::getUser() . '/files/' . $newPath);
		$absOldPath = Filesystem::normalizePath('/' . \OC_User::getUser() . '/files/' . $oldPath);

		$mountManager = Filesystem::getMountManager();
		$mountedShares = $mountManager->findIn('/' . \OC_User::getUser() . '/files/' . $oldPath);
		foreach ($mountedShares as $mount) {
			/** @var MountPoint $mount */
			if ($mount->getStorage()->instanceOfStorage(ISharedStorage::class)) {
				$mountPoint = $mount->getMountPoint();
				$target = str_replace($absOldPath, $absNewPath, $mountPoint);
				$mount->moveMount($target);
			}
		}
	}
}
