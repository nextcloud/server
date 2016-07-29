<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Aldo "xoen" Giambelluca <xoen@xoen.org>
 * @author Dominik Schmidt <dev@dominik-schmidt.de>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sam Tuke <mail@samtuke.com>
 * @author Tigran Mkrtchyan <tigran.mkrtchyan@desy.de>
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

/**
 * error code for functions not provided by the user backend
 * @deprecated Use \OC_User_Backend::NOT_IMPLEMENTED instead
 */
define('OC_USER_BACKEND_NOT_IMPLEMENTED',   -501);

/**
 * actions that user backends can define
 */
/** @deprecated Use \OC_User_Backend::CREATE_USER instead */
define('OC_USER_BACKEND_CREATE_USER',       1 << 0);
/** @deprecated Use \OC_User_Backend::SET_PASSWORD instead */
define('OC_USER_BACKEND_SET_PASSWORD',      1 << 4);
/** @deprecated Use \OC_User_Backend::CHECK_PASSWORD instead */
define('OC_USER_BACKEND_CHECK_PASSWORD',    1 << 8);
/** @deprecated Use \OC_User_Backend::GET_HOME instead */
define('OC_USER_BACKEND_GET_HOME',          1 << 12);
/** @deprecated Use \OC_User_Backend::GET_DISPLAYNAME instead */
define('OC_USER_BACKEND_GET_DISPLAYNAME',   1 << 16);
/** @deprecated Use \OC_User_Backend::SET_DISPLAYNAME instead */
define('OC_USER_BACKEND_SET_DISPLAYNAME',   1 << 20);
/** @deprecated Use \OC_User_Backend::PROVIDE_AVATAR instead */
define('OC_USER_BACKEND_PROVIDE_AVATAR',    1 << 24);
/** @deprecated Use \OC_User_Backend::COUNT_USERS instead */
define('OC_USER_BACKEND_COUNT_USERS',       1 << 28);

/**
 * Abstract base class for user management. Provides methods for querying backend
 * capabilities.
 */
abstract class OC_User_Backend extends \OC\User\Backend implements \OCP\UserInterface {

}
