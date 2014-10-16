<?php
/**
* ownCloud - TagMapper class
*
* @author Bernhard Reiter
* @copyright 2014 Bernhard Reiter <ockham@raz.or.at>
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

namespace OC\Tagging;

use \OCP\AppFramework\Db\Mapper,
    \OCP\AppFramework\Db\DoesNotExistException,
    \OCP\IDb;

/**
 * Mapper for Tag entity
 */
class TagMapper extends Mapper {

	/**
	* Constructor.
	*
	* @param IDb $db Instance of the Db abstraction layer.
	*/
	public function __construct(IDb $db) {
		parent::__construct($db, 'vcategory', 'OC\Tagging\Tag');
	}

	/**
	* Load tags from the database.
	*
	* @param array|string $owners The user(s) whose tags we are going to load.
	* @param string $type The type of item for which we are loading tags.
	* @return array An array of Tag objects.
	*/
	public function loadTags($owners, $type) {
		if(!is_array($owners)) {
			$owners = array($owners);
		}

		$sql = 'SELECT `id`, `uid`, `type`, `category` FROM `' . $this->getTableName() . '` '
			. 'WHERE `uid` IN (' . str_repeat('?,', count($owners)-1) . '?) AND `type` = ? ORDER BY `category`';
		return $this->findEntities($sql, array_merge($owners, array($type)));
	}

	/**
	* Check if a given Tag object already exists in the database.
	*
	* @param Tag $tag The tag to look for in the database.
	* @return bool
	*/
	public function tagExists($tag) {
		$sql = 'SELECT `id`, `uid`, `type`, `category` FROM `' . $this->getTableName() . '` '
			. 'WHERE `uid` = ? AND `type` = ? AND `category` = ?';
		try {
			$this->findEntity($sql, array($tag->getOwner(), $tag->getType(), $tag->getName()));
		} catch (DoesNotExistException $e) {
			return false;
		}
		return true;
	}
}

