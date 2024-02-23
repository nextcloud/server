<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OC\Encryption;

use OC\Files\Filesystem;
use OC\Files\SetupManager;
use OC\Files\View;
use Psr\Log\LoggerInterface;

class HookManager {
	private static ?Update $updater = null;

	public static function postShared($params): void {
		self::getUpdate()->postShared($params);
	}
	public static function postUnshared($params): void {
		// In case the unsharing happens in a background job, we don't have
		// a session and we load instead the user from the UserManager
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
				throw new \Exception("Inconsistent data, File unshared, but owner not found. Should not happen");
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
				\OC::$server->getEncryptionFilesHelper(),
				\OC::$server->get(LoggerInterface::class),
				$uid
			);
		}

		return self::$updater;
	}
}
