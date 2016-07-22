<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\App;

use OCP\IUser;

/**
 * Interface IAppManager
 *
 * @package OCP\App
 * @since 8.0.0
 */
interface IAppManager {
	/**
	 * Check if an app is enabled for user
	 *
	 * @param string $appId
	 * @param \OCP\IUser $user (optional) if not defined, the currently loggedin user will be used
	 * @return bool
	 * @since 8.0.0
	 */
	public function isEnabledForUser($appId, $user = null);

	/**
	 * Check if an app is installed in the instance
	 *
	 * @param string $appId
	 * @return bool
	 * @since 8.0.0
	 */
	public function isInstalled($appId);

	/**
	 * Enable an app for every user
	 *
	 * @param string $appId
	 * @since 8.0.0
	 */
	public function enableApp($appId);

	/**
	 * Enable an app only for specific groups
	 *
	 * @param string $appId
	 * @param \OCP\IGroup[] $groups
	 * @since 8.0.0
	 */
	public function enableAppForGroups($appId, $groups);

	/**
	 * Disable an app for every user
	 *
	 * @param string $appId
	 * @since 8.0.0
	 */
	public function disableApp($appId);

	/**
	 * List all apps enabled for a user
	 *
	 * @param \OCP\IUser $user
	 * @return string[]
	 * @since 8.1.0
	 */
	public function getEnabledAppsForUser(IUser $user);

	/**
	 * List all installed apps
	 *
	 * @return string[]
	 * @since 8.1.0
	 */
	public function getInstalledApps();

	/**
	 * Clear the cached list of apps when enabling/disabling an app
	 * @since 8.1.0
	 */
	public function clearAppsCache();

	/**
	 * @param string $appId
	 * @return boolean
	 * @since 9.0.0
	 */
	public function isShipped($appId);

	/**
	 * @return string[]
	 * @since 9.0.0
	 */
	public function getAlwaysEnabledApps();
}
