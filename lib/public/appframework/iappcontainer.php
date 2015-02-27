<?php
/**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2013 Thomas Müller deepdiver@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\AppFramework;

use OCP\AppFramework\IApi;
use OCP\IContainer;

/**
 * Class IAppContainer
 * @package OCP\AppFramework
 *
 * This container interface provides short cuts for app developers to access predefined app service.
 */
interface IAppContainer extends IContainer {

	/**
	 * used to return the appname of the set application
	 * @return string the name of your application
	 */
	function getAppName();

	/**
	 * @deprecated implements only deprecated methods
	 * @return IApi
	 */
	function getCoreApi();

	/**
	 * @return \OCP\IServerContainer
	 */
	function getServer();

	/**
	 * @param string $middleWare
	 * @return boolean
	 */
	function registerMiddleWare($middleWare);

	/**
	 * @deprecated use IUserSession->isLoggedIn()
	 * @return boolean
	 */
	function isLoggedIn();

	/**
	 * @deprecated use IGroupManager->isAdmin($userId)
	 * @return boolean
	 * @deprecated use the groupmanager instead to find out if the user is in
	 * the admin group
	 */
	function isAdminUser();

	/**
	 * @deprecated use the ILogger instead
	 * @param string $message
	 * @param string $level
	 * @return mixed
	 */
	function log($message, $level);

}
