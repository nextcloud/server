<?php

/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Dominik Schmidt
 * @copyright 2010 Frank Karlitschek karlitschek@kde.org
 * @copyright 2011 Dominik Schmidt dev@dominik-schmidt.de
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

/**
 * error code for functions not provided by the user backend
 */
define('OC_USER_BACKEND_NOT_IMPLEMENTED',   -501);

/**
 * actions that user backends can define
 */
define('OC_USER_BACKEND_CREATE_USER',       0x000001);
define('OC_USER_BACKEND_DELETE_USER',       0x000010);
define('OC_USER_BACKEND_SET_PASSWORD',      0x000100);
define('OC_USER_BACKEND_CHECK_PASSWORD',    0x001000);
define('OC_USER_BACKEND_GET_USERS',         0x010000);
define('OC_USER_BACKEND_USER_EXISTS',       0x100000);


/**
 * abstract base class for user management
 * subclass this for your own backends and see OC_User_Example for descriptions
 */
abstract class OC_User_Backend {

	protected $possibleActions = array(
		OC_USER_BACKEND_CREATE_USER => 'createUser',
		OC_USER_BACKEND_DELETE_USER => 'deleteUser',
		OC_USER_BACKEND_SET_PASSWORD => 'setPassword',
		OC_USER_BACKEND_CHECK_PASSWORD => 'checkPassword',
		OC_USER_BACKEND_GET_USERS => 'getUsers',
		OC_USER_BACKEND_USER_EXISTS => 'userExists'
	);

	/**
	* @brief Get all supported actions
	* @returns bitwise-or'ed actions
	*
	* Returns the supported actions as int to be
	* compared with OC_USER_BACKEND_CREATE_USER etc.
	*/
	public function getSupportedActions(){
		$actions = 0;
		foreach($this->possibleActions AS $action => $methodName){
			if(method_exists($this, $methodName)) {
				$actions |= $action;
			}
		}

		return $actions;
	}

	/**
	* @brief Check if backend implements actions
	* @param $actions bitwise-or'ed actions
	* @returns boolean
	*
	* Returns the supported actions as int to be
	* compared with OC_USER_BACKEND_CREATE_USER etc.
	*/
	public function implementsActions($actions){
		return (bool)($this->getSupportedActions() & $actions);
	}
}
