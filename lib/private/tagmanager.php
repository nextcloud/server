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

use OC\Tagging\TagMapper;

class TagManager implements \OCP\ITagManager {

	/**
	 * User session
	 *
	 * @var \OCP\IUserSession
	 */
	private $userSession;

	/**
	 * TagMapper
	 *
	 * @var TagMapper
	 */
	private $mapper;

	/**
	* Constructor.
	*
	* @param TagMapper $mapper Instance of the TagMapper abstraction layer.
	* @param \OCP\IUserSession $userSession the user session
	*/
	public function __construct(TagMapper $mapper, \OCP\IUserSession $userSession) {
		$this->mapper = $mapper;
		$this->userSession = $userSession;

	}

	/**
	* Create a new \OCP\ITags instance and load tags from db.
	*
	* @see \OCP\ITags
	* @param string $type The type identifier e.g. 'contact' or 'event'.
	* @param array $defaultTags An array of default tags to be used if none are stored.
	* @param boolean $includeShared Whether to include tags for items shared with this user by others.
	* @param string $userId user for which to retrieve the tags, defaults to the currently
	* logged in user
	* @return \OCP\ITags
	*/
	public function load($type, $defaultTags = array(), $includeShared = false, $userId = null) {
		if (is_null($userId)) {
			$userId = $this->userSession->getUser()->getUId();
		}
		return new Tags($this->mapper, $userId, $type, $defaultTags, $includeShared);
	}

}
