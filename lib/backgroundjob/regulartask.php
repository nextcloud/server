<?php
/**
* ownCloud
*
* @author Jakob Sack
* @copyright 2012 Jakob Sack owncloud@jakobsack.de
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
 * This class manages the regular tasks.
 */
class OC_BackgroundJob_RegularTask{
	static private $registered = array();

	/**
	 * @brief creates a regular task
	 * @param $klass class name
	 * @param $method method name
	 * @return true
	 */
	static public function register( $klass, $method ) {
		// Create the data structure
		self::$registered["$klass-$method"] = array( $klass, $method );

		// No chance for failure ;-)
		return true;
	}

	/**
	 * @brief gets all regular tasks
	 * @return associative array
	 *
	 * key is string "$klass-$method", value is array( $klass, $method )
	 */
	static public function all() {
		return self::$registered;
	}
}
