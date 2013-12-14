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
interface IAppContainer extends IContainer{

	/**
	 * used to return the appname of the set application
	 * @return string the name of your application
	 */
	function getAppName();

	/**
	 * @return IApi
	 */
	function getCoreApi();

	/**
	 * @return \OCP\IServerContainer
	 */
	function getServer();

	/**
	 * @param Middleware $middleWare
	 * @return boolean
	 */
	function registerMiddleWare(Middleware $middleWare);

	/**
	 * @return boolean
	 */
	function isLoggedIn();

	/**
	 * @return boolean
	 */
	function isAdminUser();

	/**
	 * @param $message
	 * @param $level
	 * @return mixed
	 */
	function log($message, $level);

}
