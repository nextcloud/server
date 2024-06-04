<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_Sharing;

use OC\Files\Cache\FileAccess;
use OC\Files\Mount\MountPoint;
use OCP\Constants;
use OCP\Files\Folder;
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
			throw new \Exception("user folder has no owner");
		}

		$src = $userFolder->get($path);

		$shareManager = \OC::$server->getShareManager();

		// FIXME: should CIRCLES be included here ??
		$shares = $shareManager->getSharesBy($user->getUID(), IShare::TYPE_USER, $src, false, -1);
		$shares = array_merge($shares, $shareManager->getSharesBy($user->getUID(), IShare::TYPE_GROUP, $src, false, -1));
		$shares = array_merge($shares, $shareManager->getSharesBy($user->getUID(), IShare::TYPE_ROOM, $src, false, -1));

		if ($src instanceof Folder) {
			$cacheAccess = Server::get(FileAccess::class);

			$sourceStorageId = $src->getStorage()->getCache()->getNumericStorageId();
			$sourceInternalPath = $src->getInternalPath();
			$subShares = array_merge(
				$shareManager->getSharesBy($user->getUID(), IShare::TYPE_USER),
				$shareManager->getSharesBy($user->getUID(), IShare::TYPE_GROUP),
				$shareManager->getSharesBy($user->getUID(), IShare::TYPE_ROOM),
			);
			$shareSourceIds = array_map(fn (IShare $share) => $share->getNodeId(), $subShares);
			$shareSources = $cacheAccess->getByFileIdsInStorage($shareSourceIds, $sourceStorageId);
			foreach ($subShares as $subShare) {
				$shareCacheEntry = $shareSources[$subShare->getNodeId()] ?? null;
				if (
					$shareCacheEntry &&
					str_starts_with($shareCacheEntry->getPath(), $sourceInternalPath . '/')
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
		$mountManager = \OC::$server->getMountManager();
		$dstMount = $mountManager->find($src->getPath());

		//Ownership is moved over
		foreach ($shares as $share) {
			if (
				$share->getShareType() !== IShare::TYPE_USER &&
				$share->getShareType() !== IShare::TYPE_GROUP &&
				$share->getShareType() !== IShare::TYPE_ROOM
			) {
				continue;
			}

			if ($dstMount instanceof \OCA\Files_Sharing\SharedMount) {
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
			$shareManager->updateShare($share);
		}
	}

	/**
	 * rename mount point from the children if the parent was renamed
	 *
	 * @param string $oldPath old path relative to data/user/files
	 * @param string $newPath new path relative to data/user/files
	 */
	private static function renameChildren($oldPath, $newPath) {
		$absNewPath = \OC\Files\Filesystem::normalizePath('/' . \OC_User::getUser() . '/files/' . $newPath);
		$absOldPath = \OC\Files\Filesystem::normalizePath('/' . \OC_User::getUser() . '/files/' . $oldPath);

		$mountManager = \OC\Files\Filesystem::getMountManager();
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
