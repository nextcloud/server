<?php
/**
 * @author David Vicente <dvicente@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */


namespace OCA\user_ldap\lib;


class AccessFactory {


	public function createAccess($configPrefix) {
		$ocConfig = \OC::$server->getConfig();
		$fs       = new FilesystemHelper();
		$log      = new LogWrapper();
		$avatarM  = \OC::$server->getAvatarManager();
		$db       = \OC::$server->getDatabaseConnection();
		$coreUserManager = \OC::$server->getUserManager();
		$ldapWrapper = new \OCA\user_ldap\lib\LDAP();
		$userManager =
			new user\Manager($ocConfig, $fs, $log, $avatarM, new \OCP\Image(), $db, $coreUserManager);
		$connector = new Connection($ldapWrapper, $configPrefix);
		$access = new Access($connector, $ldapWrapper, $userManager);
		return $access;
	}

}
