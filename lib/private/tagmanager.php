<?php
/**
* ownCloud
*
* @author Thomas Tanghus
* @copyright 2013 Thomas Tanghus <thomas@tanghus.net>
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
 * Factory class creating instances of \OCP\ITags
 *
 * A tag can be e.g. 'Family', 'Work', 'Chore', 'Special Occation' or
 * anything else that is either parsed from a vobject or that the user chooses
 * to add.
 * Tag names are not case-sensitive, but will be saved with the case they
 * are entered in. If a user already has a tag 'family' for a type, and
 * tries to add a tag named 'Family' it will be silently ignored.
 */

namespace OC;

class TagManager implements \OCP\ITagManager {

	/**
	 * User
	 *
	 * @var string
	 */
	private $user = null;

	/**
	* Constructor.
	*
	* @param string $user The user whos data the object will operate on.
	*/
	public function __construct($user) {

		$this->user = $user;

	}

	/**
	* Create a new \OCP\ITags instance and load tags from db.
	*
	* @see \OCP\ITags
	* @param string $type The type identifier e.g. 'contact' or 'event'.
	* @param array $defaultTags An array of default tags to be used if none are stored.
	* @return \OCP\ITags
	*/
	public function load($type, $defaultTags=array()) {
		return new Tags($this->user, $type, $defaultTags);
	}

}