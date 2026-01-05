<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Encryption;

use OC\Files\Filesystem;
use OC\Files\SetupManager;
use OC\Files\View;
use OCP\Encryption\IFile;
use Psr\Log\LoggerInterface;

class HookManager {
	private static ?Update $updater = null;

	public static function postShared($params): void {
		self::getUpdate()->postShared($params);
	}
	public static function postUnshared($params): void {
		// In case the unsharing happens in a background job, we don't have
		// a session and we load instead the user from the UserManager
		if (Filesystem::getView() === null) {
			$uidOwner = $params['uidOwner'] ?? '';
			if (is_string($uidOwner) && $uidOwner !== '') {
				$user = \OC::$server->getUserManager()->get($uidOwner);
				if ($user !== null) {
					$setupManager = \OC::$server->get(SetupManager::class);
					if (!$setupManager->isSetupComplete($user)) {
						$setupManager->setupForUser($user);
					}
				}
			}
		}
		if (Filesystem::getView() === null) {
			return;
		}
		$path = Filesystem::getPath($params['fileSource']);
		$owner = Filesystem::getOwner($path);
		self::getUpdate($owner)->postUnshared($params);
	}

	public static function postRename($params): void {
		self::getUpdate()->postRename($params);
	}

	public static function postRestore($params): void {
		self::getUpdate()->postRestore($params);
	}

	private static function getUpdate(?string $owner = null): Update {
		if (is_null(self::$updater)) {
			$user = \OC::$server->getUserSession()->getUser();
			if (!$user && $owner) {
				$user = \OC::$server->getUserManager()->get($owner);
			}
			if (!$user) {
				throw new \Exception('Inconsistent data, File unshared, but owner not found. Should not happen');
			}

			$uid = '';
			if ($user) {
				$uid = $user->getUID();
			}

			$setupManager = \OC::$server->get(SetupManager::class);
			if (!$setupManager->isSetupComplete($user)) {
				$setupManager->setupForUser($user);
			}

			self::$updater = new Update(
				new View(),
				new Util(
					new View(),
					\OC::$server->getUserManager(),
					\OC::$server->getGroupManager(),
					\OC::$server->getConfig()),
				Filesystem::getMountManager(),
				\OC::$server->getEncryptionManager(),
				\OC::$server->get(IFile::class),
				\OC::$server->get(LoggerInterface::class),
				$uid
			);
		}

		return self::$updater;
	}
}
