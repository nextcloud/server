<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Tom Needham <tom@owncloud.com>
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
 * Public interface of ownCloud for apps to use.
 * API Class
 *
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * This class provides functions to manage apps in ownCloud
 * @since 5.0.0
 * @deprecated 9.1.0 Use the AppFramework
 */
class API {

	/**
	 * API authentication levels
	 * @since 8.1.0
	 */
	const GUEST_AUTH = 0;
	const USER_AUTH = 1;
	const SUBADMIN_AUTH = 2;
	const ADMIN_AUTH = 3;

	/**
	 * API Response Codes
	 * @since 8.1.0
	 */
	const RESPOND_UNAUTHORISED = 997;
	const RESPOND_SERVER_ERROR = 996;
	const RESPOND_NOT_FOUND = 998;
	const RESPOND_UNKNOWN_ERROR = 999;

	/**
	 * registers an api call
	 * @param string $method the http method
	 * @param string $url the url to match
	 * @param callable $action the function to run
	 * @param string $app the id of the app registering the call
	 * @param int $authLevel the level of authentication required for the call (See `self::*_AUTH` constants)
	 * @param array $defaults
	 * @param array $requirements
	 * @since 5.0.0
	 * @deprecated 9.1.0 Use the AppFramework
	 */
	public static function register($method, $url, $action, $app, $authLevel = self::USER_AUTH,
		$defaults = array(), $requirements = array()){
		\OC_API::register($method, $url, $action, $app, $authLevel, $defaults, $requirements);
	}

}
