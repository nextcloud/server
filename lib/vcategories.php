<?php
/**
* ownCloud
*
* @author Thomas Tanghus
* @copyright 2012 Thomas Tanghus <thomas@tanghus.net>
* @copyright 2012 Bart Visscher bartv@thisnet.nl
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

OC_Hook::connect('OC_User', 'post_deleteUser', 'OC_VCategories', 'post_deleteUser');

/**
 * Class for easy access to categories in VCARD, VEVENT, VTODO and VJOURNAL.
 * A Category can be e.g. 'Family', 'Work', 'Chore', 'Special Occation' or
 * anything else that is either parsed from a vobject or that the user chooses
 * to add.
 * Category names are not case-sensitive, but will be saved with the case they
 * are entered in. If a user already has a category 'family' for a type, and
 * tries to add a category named 'Family' it will be silently ignored.
 */
class OC_VCategories {

	/**
	 * Categories
	 */
	private $categories = array();

	/**
	 * Used for storing objectid/categoryname pairs while rescanning.
	 */
	private static $relations = array();

	private $type = null;
	private $user = null;

	const CATEGORY_TABLE = '*PREFIX*vcategory';
	const RELATION_TABLE = '*PREFIX*vcategory_to_object';

	const CATEGORY_FAVORITE = '_$!<Favorite>!$_';

	const FORMAT_LIST = 0;
	const FORMAT_MAP  = 1;

	/**
	* @brief Constructor.
	* @param $type The type identifier e.g. 'contact' or 'event'.
	* @param $user The user whos data the object will operate on. This
	*   parameter should normally be omitted but to make an app able to
	*   update categories for all users it is made possible to provide it.
	* @param $defcategories An array of default categories to be used if none is stored.
	*/
	public function __construct($type, $user=null, $defcategories=array()) {
		$this->type = $type;
		$this->user = is_null($user) ? OC_User::getUser() : $user;

		$this->loadCategories();
		OCP\Util::writeLog('core', __METHOD__ . ', categories: '
			. print_r($this->categories, true),
			OCP\Util::DEBUG
		);

		if($defcategories && count($this->categories) === 0) {
			$this->addMulti($defcategories, true);
		}
	}

	/**
	* @brief Load categories from db.
	*/
	private function loadCategories() {
		$this->categories = array();
		$result = null;
		$sql = 'SELECT `id`, `category` FROM `' . self::CATEGORY_TABLE . '` '
			. 'WHERE `uid` = ? AND `type` = ? ORDER BY `category`';
		try {
			$stmt = OCP\DB::prepare($sql);
			$result = $stmt->execute(array($this->user, $this->type));
			if (OC_DB::isError($result)) {
				OC_Log::write('core', __METHOD__. 'DB error: ' . OC_DB::getErrorMessage($result), OC_Log::ERROR);
			}
		} catch(Exception $e) {
			OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),
				OCP\Util::ERROR);
		}

		if(!is_null($result)) {
			while( $row = $result->fetchRow()) {
				// The keys are prefixed because array_search wouldn't work otherwise :-/
				$this->categories[$row['id']] = $row['category'];
			}
		}
		OCP\Util::writeLog('core', __METHOD__.', categories: ' . print_r($this->categories, true),
			OCP\Util::DEBUG);
	}


	/**
	* @brief Check if any categories are saved for this type and user.
	* @returns boolean.
	* @param $type The type identifier e.g. 'contact' or 'event'.
	* @param $user The user whos categories will be checked. If not set current user will be used.
	*/
	public static function isEmpty($type, $user = null) {
		$user = is_null($user) ? OC_User::getUser() : $user;
		$sql = 'SELECT COUNT(*) FROM `' . self::CATEGORY_TABLE . '` '
			. 'WHERE `uid` = ? AND `type` = ?';
		try {
			$stmt = OCP\DB::prepare($sql);
			$result = $stmt->execute(array($user, $type));
			if (OC_DB::isError($result)) {
				OC_Log::write('core', __METHOD__. 'DB error: ' . OC_DB::getErrorMessage($result), OC_Log::ERROR);
				return false;
			}
			return ($result->numRows() == 0);
		} catch(Exception $e) {
			OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),
				OCP\Util::ERROR);
			return false;
		}
	}

	/**
	* @brief Get the categories for a specific user.
	* @param
	* @returns array containing the categories as strings.
	*/
	public function categories($format = null) {
		if(!$this->categories) {
			return array();
		}
		$categories = array_values($this->categories);
		uasort($categories, 'strnatcasecmp');
		if($format == self::FORMAT_MAP) {
			$catmap = array();
			foreach($categories as $category) {
				if($category !== self::CATEGORY_FAVORITE) {
					$catmap[] = array(
						'id' => $this->array_searchi($category, $this->categories),
						'name' => $category
						);
				}
			}
			return $catmap;
		}

		// Don't add favorites to normal categories.
		$favpos = array_search(self::CATEGORY_FAVORITE, $categories);
		if($favpos !== false) {
			return array_splice($categories, $favpos);
		} else {
			return $categories;
		}
	}

	/**
	* Get the a list if items belonging to $category.
	*
	* Throws an exception if the category could not be found.
	*
	* @param string|integer $category Category id or name.
	* @returns array An array of object ids or false on error.
	*/
	public function idsForCategory($category) {
		$result = null;
		if(is_numeric($category)) {
			$catid = $category;
		} elseif(is_string($category)) {
			$catid = $this->array_searchi($category, $this->categories);
		}
		OCP\Util::writeLog('core', __METHOD__.', category: '.$catid.' '.$category, OCP\Util::DEBUG);
		if($catid === false) {
			$l10n = OC_L10N::get('core');
			throw new Exception(
				$l10n->t('Could not find category "%s"', $category)
			);
		}

		$ids = array();
		$sql = 'SELECT `objid` FROM `' . self::RELATION_TABLE
			. '` WHERE `categoryid` = ?';

		try {
			$stmt = OCP\DB::prepare($sql);
			$result = $stmt->execute(array($catid));
			if (OC_DB::isError($result)) {
				OC_Log::write('core', __METHOD__. 'DB error: ' . OC_DB::getErrorMessage($result), OC_Log::ERROR);
				return false;
			}
		} catch(Exception $e) {
			OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),
				OCP\Util::ERROR);
			return false;
		}

		if(!is_null($result)) {
			while( $row = $result->fetchRow()) {
				$ids[] = (int)$row['objid'];
			}
		}

		return $ids;
	}

	/**
	* Get the a list if items belonging to $category.
	*
	* Throws an exception if the category could not be found.
	*
	* @param string|integer $category Category id or name.
	* @param array $tableinfo Array in the form {'tablename' => table, 'fields' => ['field1', 'field2']}
	* @param int $limit
	* @param int $offset
	*
	* This generic method queries a table assuming that the id
	* field is called 'id' and the table name provided is in
	* the form '*PREFIX*table_name'.
	*
	* If the category name cannot be resolved an exception is thrown.
	*
	* TODO: Maybe add the getting permissions for objects?
	*
	* @returns array containing the resulting items or false on error.
	*/
	public function itemsForCategory($category, $tableinfo, $limit = null, $offset = null) {
		$result = null;
		if(is_numeric($category)) {
			$catid = $category;
		} elseif(is_string($category)) {
			$catid = $this->array_searchi($category, $this->categories);
		}
		OCP\Util::writeLog('core', __METHOD__.', category: '.$catid.' '.$category, OCP\Util::DEBUG);
		if($catid === false) {
			$l10n = OC_L10N::get('core');
			throw new Exception(
				$l10n->t('Could not find category "%s"', $category)
			);
		}
		$fields = '';
		foreach($tableinfo['fields'] as $field) {
			$fields .= '`' . $tableinfo['tablename'] . '`.`' . $field . '`,';
		}
		$fields = substr($fields, 0, -1);

		$items = array();
		$sql = 'SELECT `' . self::RELATION_TABLE . '`.`categoryid`, ' . $fields
			. ' FROM `' . $tableinfo['tablename'] . '` JOIN `'
			. self::RELATION_TABLE . '` ON `' . $tableinfo['tablename']
			. '`.`id` = `' . self::RELATION_TABLE . '`.`objid` WHERE `'
			. self::RELATION_TABLE . '`.`categoryid` = ?';

		try {
			$stmt = OCP\DB::prepare($sql, $limit, $offset);
			$result = $stmt->execute(array($catid));
			if (OC_DB::isError($result)) {
				OC_Log::write('core', __METHOD__. 'DB error: ' . OC_DB::getErrorMessage($result), OC_Log::ERROR);
				return false;
			}
		} catch(Exception $e) {
			OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),
				OCP\Util::ERROR);
			return false;
		}

		if(!is_null($result)) {
			while( $row = $result->fetchRow()) {
				$items[] = $row;
			}
		}
		//OCP\Util::writeLog('core', __METHOD__.', count: ' . count($items), OCP\Util::DEBUG);
		//OCP\Util::writeLog('core', __METHOD__.', sql: ' . $sql, OCP\Util::DEBUG);

		return $items;
	}

	/**
	* @brief Checks whether a category is already saved.
	* @param $name The name to check for.
	* @returns bool
	*/
	public function hasCategory($name) {
		return $this->in_arrayi($name, $this->categories);
	}

	/**
	* @brief Add a new category.
	* @param $name A string with a name of the category
	* @returns int the id of the added category or false if it already exists.
	*/
	public function add($name) {
		OCP\Util::writeLog('core', __METHOD__.', name: ' . $name, OCP\Util::DEBUG);
		if($this->hasCategory($name)) {
			OCP\Util::writeLog('core', __METHOD__.', name: ' . $name. ' exists already', OCP\Util::DEBUG);
			return false;
		}
		try {
			OCP\DB::insertIfNotExist(self::CATEGORY_TABLE,
				array(
					'uid' => $this->user,
					'type' => $this->type,
					'category' => $name,
				));
			} catch(Exception $e) {
				OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),
					OCP\Util::ERROR);
				return false;
			}
		$id = OCP\DB::insertid(self::CATEGORY_TABLE);
		OCP\Util::writeLog('core', __METHOD__.', id: ' . $id, OCP\Util::DEBUG);
		$this->categories[$id] = $name;
		return $id;
	}

	/**
	* @brief Add a new category.
	* @param $names A string with a name or an array of strings containing
	* the name(s) of the categor(y|ies) to add.
	* @param $sync bool When true, save the categories
	* @param $id int Optional object id to add to this|these categor(y|ies)
	* @returns bool Returns false on error.
	*/
	public function addMulti($names, $sync=false, $id = null) {
		if(!is_array($names)) {
			$names = array($names);
		}
		$names = array_map('trim', $names);
		$newones = array();
		foreach($names as $name) {
			if(($this->in_arrayi(
				$name, $this->categories) == false) && $name != '') {
				$newones[] = $name;
			}
			if(!is_null($id) ) {
				// Insert $objectid, $categoryid  pairs if not exist.
				self::$relations[] = array('objid' => $id, 'category' => $name);
			}
		}
		$this->categories = array_merge($this->categories, $newones);
		if($sync === true) {
			$this->save();
		}

		return true;
	}

	/**
	* @brief Extracts categories from a vobject and add the ones not already present.
	* @param $vobject The instance of OC_VObject to load the categories from.
	*/
	public function loadFromVObject($id, $vobject, $sync=false) {
		$this->addMulti($vobject->getAsArray('CATEGORIES'), $sync, $id);
	}

	/**
	* @brief Reset saved categories and rescan supplied vobjects for categories.
	* @param $objects An array of vobjects (as text).
	* To get the object array, do something like:
	*	// For Addressbook:
	*	$categories = new OC_VCategories('contacts');
	*	$stmt = OC_DB::prepare( 'SELECT `carddata` FROM `*PREFIX*contacts_cards`' );
	*	$result = $stmt->execute();
	*	$objects = array();
	*	if(!is_null($result)) {
	*		while( $row = $result->fetchRow()){
	*			$objects[] = array($row['id'], $row['carddata']);
	*		}
	*	}
	*	$categories->rescan($objects);
	*/
	public function rescan($objects, $sync=true, $reset=true) {

		if($reset === true) {
			$result = null;
			// Find all objectid/categoryid pairs.
			try {
				$stmt = OCP\DB::prepare('SELECT `id` FROM `' . self::CATEGORY_TABLE . '` '
					. 'WHERE `uid` = ? AND `type` = ?');
				$result = $stmt->execute(array($this->user, $this->type));
				if (OC_DB::isError($result)) {
					OC_Log::write('core', __METHOD__. 'DB error: ' . OC_DB::getErrorMessage($result), OC_Log::ERROR);
					return false;
				}
			} catch(Exception $e) {
				OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),
					OCP\Util::ERROR);
			}

			// And delete them.
			if(!is_null($result)) {
				$stmt = OCP\DB::prepare('DELETE FROM `' . self::RELATION_TABLE . '` '
					. 'WHERE `categoryid` = ? AND `type`= ?');
				while( $row = $result->fetchRow()) {
					$stmt->execute(array($row['id'], $this->type));
				}
			}
			try {
				$stmt = OCP\DB::prepare('DELETE FROM `' . self::CATEGORY_TABLE . '` '
					. 'WHERE `uid` = ? AND `type` = ?');
				$result = $stmt->execute(array($this->user, $this->type));
				if (OC_DB::isError($result)) {
					OC_Log::write('core', __METHOD__. 'DB error: ' . OC_DB::getErrorMessage($result), OC_Log::ERROR);
					return;
				}
			} catch(Exception $e) {
				OCP\Util::writeLog('core', __METHOD__ . ', exception: '
					. $e->getMessage(), OCP\Util::ERROR);
				return;
			}
			$this->categories = array();
		}
		// Parse all the VObjects
		foreach($objects as $object) {
			$vobject = OC_VObject::parse($object[1]);
			if(!is_null($vobject)) {
				// Load the categories
				$this->loadFromVObject($object[0], $vobject, $sync);
			} else {
				OC_Log::write('core', __METHOD__ . ', unable to parse. ID: ' . ', '
					. substr($object, 0, 100) . '(...)', OC_Log::DEBUG);
			}
		}
		$this->save();
	}

	/**
	 * @brief Save the list with categories
	 */
	private function save() {
		if(is_array($this->categories)) {
			foreach($this->categories as $category) {
				try {
					OCP\DB::insertIfNotExist(self::CATEGORY_TABLE,
						array(
							'uid' => $this->user,
							'type' => $this->type,
							'category' => $category,
						));
				} catch(Exception $e) {
					OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),
						OCP\Util::ERROR);
				}
			}
			// reload categories to get the proper ids.
			$this->loadCategories();
			// Loop through temporarily cached objectid/categoryname pairs
			// and save relations.
			$categories = $this->categories;
			// For some reason this is needed or array_search(i) will return 0..?
			ksort($categories);
			foreach(self::$relations as $relation) {
				$catid = $this->array_searchi($relation['category'], $categories);
				OC_Log::write('core', __METHOD__ . 'catid, ' . $relation['category'] . ' ' . $catid, OC_Log::DEBUG);
				if($catid) {
					try {
						OCP\DB::insertIfNotExist(self::RELATION_TABLE,
							array(
								'objid' => $relation['objid'],
								'categoryid' => $catid,
								'type' => $this->type,
								));
					} catch(Exception $e) {
						OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),
							OCP\Util::ERROR);
					}
				}
			}
			self::$relations = array(); // reset
		} else {
			OC_Log::write('core', __METHOD__.', $this->categories is not an array! '
				. print_r($this->categories, true), OC_Log::ERROR);
		}
	}

	/**
	* @brief Delete categories and category/object relations for a user.
	* For hooking up on post_deleteUser
	* @param string $uid The user id for which entries should be purged.
	*/
	public static function post_deleteUser($arguments) {
		// Find all objectid/categoryid pairs.
		$result = null;
		try {
			$stmt = OCP\DB::prepare('SELECT `id` FROM `' . self::CATEGORY_TABLE . '` '
				. 'WHERE `uid` = ?');
			$result = $stmt->execute(array($arguments['uid']));
			if (OC_DB::isError($result)) {
				OC_Log::write('core', __METHOD__. 'DB error: ' . OC_DB::getErrorMessage($result), OC_Log::ERROR);
			}
		} catch(Exception $e) {
			OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),
				OCP\Util::ERROR);
		}

		if(!is_null($result)) {
			try {
				$stmt = OCP\DB::prepare('DELETE FROM `' . self::RELATION_TABLE . '` '
					. 'WHERE `categoryid` = ?');
				while( $row = $result->fetchRow()) {
					try {
						$stmt->execute(array($row['id']));
					} catch(Exception $e) {
						OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),
							OCP\Util::ERROR);
					}
				}
			} catch(Exception $e) {
				OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),
					OCP\Util::ERROR);
			}
		}
		try {
			$stmt = OCP\DB::prepare('DELETE FROM `' . self::CATEGORY_TABLE . '` '
				. 'WHERE `uid` = ? AND');
			$result = $stmt->execute(array($arguments['uid']));
			if (OC_DB::isError($result)) {
				OC_Log::write('core', __METHOD__. 'DB error: ' . OC_DB::getErrorMessage($result), OC_Log::ERROR);
			}
		} catch(Exception $e) {
			OCP\Util::writeLog('core', __METHOD__ . ', exception: '
				. $e->getMessage(), OCP\Util::ERROR);
		}
	}

	/**
	* @brief Delete category/object relations from the db
	* @param int $id The id of the object
	* @param string $type The type of object (event/contact/task/journal).
	* 	Defaults to the type set in the instance
	* @returns boolean Returns false on error.
	*/
	public function purgeObject($id, $type = null) {
		$type = is_null($type) ? $this->type : $type;
		try {
			$stmt = OCP\DB::prepare('DELETE FROM `' . self::RELATION_TABLE . '` '
					. 'WHERE `objid` = ? AND `type`= ?');
			$result = $stmt->execute(array($id, $type));
			if (OC_DB::isError($result)) {
				OC_Log::write('core', __METHOD__. 'DB error: ' . OC_DB::getErrorMessage($result), OC_Log::ERROR);
				return false;
			}
		} catch(Exception $e) {
			OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),
				OCP\Util::ERROR);
			return false;
		}
		return true;
	}

	/**
	* Get favorites for an object type
	*
	* @param string $type The type of object (event/contact/task/journal).
	* 	Defaults to the type set in the instance
	* @returns array An array of object ids.
	*/
	public function getFavorites($type = null) {
		$type = is_null($type) ? $this->type : $type;

		try {
			return $this->idsForCategory(self::CATEGORY_FAVORITE);
		} catch(Exception $e) {
			// No favorites
			return array();
		}
	}

	/**
	* Add an object to favorites
	*
	* @param int $objid The id of the object
	* @param string $type The type of object (event/contact/task/journal).
	* 	Defaults to the type set in the instance
	* @returns boolean
	*/
	public function addToFavorites($objid, $type = null) {
		$type = is_null($type) ? $this->type : $type;
		if(!$this->hasCategory(self::CATEGORY_FAVORITE)) {
			$this->add(self::CATEGORY_FAVORITE, true);
		}
		return $this->addToCategory($objid, self::CATEGORY_FAVORITE, $type);
	}

	/**
	* Remove an object from favorites
	*
	* @param int $objid The id of the object
	* @param string $type The type of object (event/contact/task/journal).
	* 	Defaults to the type set in the instance
	* @returns boolean
	*/
	public function removeFromFavorites($objid, $type = null) {
		$type = is_null($type) ? $this->type : $type;
		return $this->removeFromCategory($objid, self::CATEGORY_FAVORITE, $type);
	}

	/**
	* @brief Creates a category/object relation.
	* @param int $objid The id of the object
	* @param int|string $category The id or name of the category
	* @param string $type The type of object (event/contact/task/journal).
	* 	Defaults to the type set in the instance
	* @returns boolean Returns false on database error.
	*/
	public function addToCategory($objid, $category, $type = null) {
		$type = is_null($type) ? $this->type : $type;
		if(is_string($category) && !is_numeric($category)) {
			if(!$this->hasCategory($category)) {
				$this->add($category, true);
			}
			$categoryid =  $this->array_searchi($category, $this->categories);
		} else {
			$categoryid = $category;
		}
		try {
			OCP\DB::insertIfNotExist(self::RELATION_TABLE,
				array(
					'objid' => $objid,
					'categoryid' => $categoryid,
					'type' => $type,
				));
		} catch(Exception $e) {
			OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),
				OCP\Util::ERROR);
			return false;
		}
		return true;
	}

	/**
	* @brief Delete single category/object relation from the db
	* @param int $objid The id of the object
	* @param int|string $category The id or name of the category
	* @param string $type The type of object (event/contact/task/journal).
	* 	Defaults to the type set in the instance
	* @returns boolean
	*/
	public function removeFromCategory($objid, $category, $type = null) {
		$type = is_null($type) ? $this->type : $type;
		$categoryid = (is_string($category) && !is_numeric($category))
			? $this->array_searchi($category, $this->categories)
			: $category;
		try {
			$sql = 'DELETE FROM `' . self::RELATION_TABLE . '` '
					. 'WHERE `objid` = ? AND `categoryid` = ? AND `type` = ?';
			OCP\Util::writeLog('core', __METHOD__.', sql: ' . $objid . ' ' . $categoryid . ' ' . $type,
				OCP\Util::DEBUG);
			$stmt = OCP\DB::prepare($sql);
			$stmt->execute(array($objid, $categoryid, $type));
		} catch(Exception $e) {
			OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),
				OCP\Util::ERROR);
			return false;
		}
		return true;
	}

	/**
	* @brief Delete categories from the db and from all the vobject supplied
	* @param $names An array of categories to delete
	* @param $objects An array of arrays with [id,vobject] (as text) pairs suitable for updating the apps object table.
	*/
	public function delete($names, array &$objects=null) {
		if(!is_array($names)) {
			$names = array($names);
		}

		OC_Log::write('core', __METHOD__ . ', before: '
			. print_r($this->categories, true), OC_Log::DEBUG);
		foreach($names as $name) {
			$id = null;
			OC_Log::write('core', __METHOD__.', '.$name, OC_Log::DEBUG);
			if($this->hasCategory($name)) {
				$id = $this->array_searchi($name, $this->categories);
				unset($this->categories[$id]);
			}
			try {
				$stmt = OCP\DB::prepare('DELETE FROM `' . self::CATEGORY_TABLE . '` WHERE '
					. '`uid` = ? AND `type` = ? AND `category` = ?');
				$result = $stmt->execute(array($this->user, $this->type, $name));
				if (OC_DB::isError($result)) {
					OC_Log::write('core', __METHOD__. 'DB error: ' . OC_DB::getErrorMessage($result), OC_Log::ERROR);
				}
			} catch(Exception $e) {
				OCP\Util::writeLog('core', __METHOD__ . ', exception: '
					. $e->getMessage(), OCP\Util::ERROR);
			}
			if(!is_null($id) && $id !== false) {
				try {
					$sql = 'DELETE FROM `' . self::RELATION_TABLE . '` '
							. 'WHERE `categoryid` = ?';
					$stmt = OCP\DB::prepare($sql);
					$result = $stmt->execute(array($id));
					if (OC_DB::isError($result)) {
						OC_Log::write('core',
							__METHOD__. 'DB error: ' . OC_DB::getErrorMessage($result),
							OC_Log::ERROR);
					}
				} catch(Exception $e) {
					OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),
						OCP\Util::ERROR);
					return false;
				}
			}
		}
		OC_Log::write('core', __METHOD__.', after: '
			. print_r($this->categories, true), OC_Log::DEBUG);
		if(!is_null($objects)) {
			foreach($objects as $key=>&$value) {
				$vobject = OC_VObject::parse($value[1]);
				if(!is_null($vobject)) {
					$object = null;
					$componentname = '';
					if (isset($vobject->VEVENT)) {
						$object = $vobject->VEVENT;
						$componentname = 'VEVENT';
					} else
					if (isset($vobject->VTODO)) {
						$object = $vobject->VTODO;
						$componentname = 'VTODO';
					} else
					if (isset($vobject->VJOURNAL)) {
						$object = $vobject->VJOURNAL;
						$componentname = 'VJOURNAL';
					} else {
						$object = $vobject;
					}
					$categories = $object->getAsArray('CATEGORIES');
					foreach($names as $name) {
						$idx = $this->array_searchi($name, $categories);
						if($idx !== false) {
							OC_Log::write('core', __METHOD__
								.', unsetting: '
								. $categories[$this->array_searchi($name, $categories)],
								OC_Log::DEBUG);
							unset($categories[$this->array_searchi($name, $categories)]);
						}
					}

					$object->setString('CATEGORIES', implode(',', $categories));
					if($vobject !== $object) {
						$vobject[$componentname] = $object;
					}
					$value[1] = $vobject->serialize();
					$objects[$key] = $value;
				} else {
					OC_Log::write('core', __METHOD__
						.', unable to parse. ID: ' . $value[0] . ', '
						. substr($value[1], 0, 50) . '(...)', OC_Log::DEBUG);
				}
			}
		}
	}

	// case-insensitive in_array
	private function in_arrayi($needle, $haystack) {
		if(!is_array($haystack)) {
			return false;
		}
		return in_array(strtolower($needle), array_map('strtolower', $haystack));
	}

	// case-insensitive array_search
	private function array_searchi($needle, $haystack) {
		if(!is_array($haystack)) {
			return false;
		}
		return array_search(strtolower($needle), array_map('strtolower', $haystack));
	}
}
