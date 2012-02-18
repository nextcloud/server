<?php
/**
* ownCloud
*
* @author Thomas Tanghus
* @copyright 2012 Thomas Tanghus <thomas@tanghus.net>
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
 * Class for easy access to categories in VCARD, VEVENT, VTODO and VJOURNAL.
 * A Category can be e.g. 'Family', 'Work', 'Chore', 'Special Occation' or anything else
 * that is either parsed from a vobject or that the user chooses to add.
 * Category names are not case-sensitive, but will be saved with the case they are
 * entered in. If a user already has a category 'family' for an app, and tries to add
 * a category named 'Family' it will be silently ignored.
 */
OC_HOOK::connect('OC_User', 'post_deleteUser', 'OC_VCategories', 'deleteUser');
class OC_VCategories {
	/**
	 * cache
	 */
	protected static $cache = array();

	/**
	 * Categories
	 */
	private $categories = array();
	
	/**
	* @brief Constructor.
	* @param $app The application identifier e.g. 'contacts' or 'calendar'.
	*/
	public function __construct($app, $user=null) {
		if(is_null($user)) {
			$user = OC_User::getUser();
		}
		// Use cache if possible - I doubt this is ever the case. Copy/paste from OC_L10N.
		if(array_key_exists($app.'::'.$user, self::$cache)){
			OC_Log::write('core','OC_Categories::ctor, using cache', OC_Log::DEBUG);
			$this->categories = self::$cache[$app.'::'.$user];
		} else {
			$result = null;
			try {
				$stmt = OC_DB::prepare('SELECT DISTINCT name FROM *PREFIX*categories WHERE userid = ? AND appid = ? ORDER BY name');
				$result = $stmt->execute(array($user, $app));
			} catch(Exception $e) {
				OC_Log::write('core','OC_VCategories::ctor, exception: '.$e->getMessage(), OC_Log::ERROR);
				OC_Log::write('core','OC_VCategories::ctor, app: '.$app.', user: '.$user, OC_Log::ERROR);
			}
			if(!is_null($result)) {
				while( $row = $result->fetchRow()){
					$this->categories[] = $row['name'];
				}
				self::$cache[$app.'::'.$user] = $this->categories;
			}
		}
	}

	/**
	* @brief Get the categories for a specific.
	* @returns array containing the categories as strings.
	*/
	public function categories() {
		return $this->categories;
	}

	/**
	* @brief Checks whether a category is already saved.
	* @param $name The name to check for.
	* @returns bool
	*/
	public function hasCategory($name) {
		return ($this->in_arrayi($name, $this->categories) == false ? false : true);
	}

	/**
	* @brief Add a new category name.
	* @param $names A string with a name or an array of strings containing the name(s) of the categor(y|ies) to add.
	* @returns bool Returns false on error.
	*/
	public function add($app, $names) {
		$user = OC_User::getUser();
		$newones = array();
		if(!is_array($names)) {
			$names = array($names);
		}
		$names = array_map('trim', $names);
		foreach($names as $name) {
			if(($this->in_arrayi($name, $this->categories) == false) && $name != '') {
				$newones[] = $name;
			}
		}
		if(count($newones) > 0) {
			$stmt = OC_DB::prepare( 'INSERT INTO *PREFIX*categories (userid,appid,name) VALUES(?,?,?)' );
			foreach($newones as $name) {
				$this->categories[] = $name;
				try {
					$result = $stmt->execute(array($user, $app, $name));
				} catch(Exception $e) {
					OC_Log::write('core','OC_VCategories::add, exception: '.$e->getMessage(), OC_Log::ERROR);
					OC_Log::write('core','OC_VCategories::add, app: '.$app.', user: '.$user.', name: '.$name, OC_Log::ERROR);
					return false;
				}
			}
			natcasesort($this->categories); // Dunno if this is necessary
		}
		return true;
	}

	/**
	* @brief Extracts categories from a vobject and add the ones not already present.
	* @param $vobject The instance of OC_VObject to load the categories from.
	* @returns bool Returns false if the name already exist (case insensitive) or on error.
	*/
	public function loadFromVObject($app, $vobject) {
		$this->add($vobject->getAsArray('CATEGORIES'));
	}

	/**
	* @brief Delete a category from the db and from all the vobject supplied
	* @param $app
	* @param $name
	* @param $objects An array of arrays with [id,vobject] (as text) pairs suitable for updating the apps object table.
	*/
	public function delete($app, $name, array &$objects) {
		if(!$this->hasCategory($name)) {
			return;
		}
		try {
			$stmt = OC_DB::prepare('DELETE FROM *PREFIX*categories WHERE UPPER(name) = ?');
			$result = $stmt->execute(array(strtoupper($name),));
		} catch(Exception $e) {
			OC_Log::write('core','OC_VCategories::delete, exception: '.$e->getMessage(), OC_Log::ERROR);
			OC_Log::write('core','OC_VCategories::delete, name: '.$name, OC_Log::ERROR);
			return false;
		}
		unset($this->categories[$this->array_searchi($name, $this->categories)]);
		foreach($objects as $key=>&$value) {
			$vobject = OC_VObject::parse($value[1]);
			if(!is_null($vobject)){
				$categories = $vobject->getAsArray('CATEGORIES');
				$idx = $this->array_searchi($name, $categories);
				if($idx) {
					unset($categories[$this->array_searchi($name, $categories)]);
					$vobject->setString('CATEGORIES', implode(',', $categories));
					$value[1] = $vobject->serialize();
					$objects[$key] = $value;
				}
			} else {
				OC_Log::write('core','OC_VCategories::delete, unable to parse. ID: '.$value[0].', '.substr($value[1], 0, 10).'(...)', OC_Log::DEBUG);
			}
		}
	}

	/**
	* @brief Delete all categories for a specific user. Connected to OC_User::post_deleteUser
	* @param $parameters The id of the user.
	* @returns bool Returns false on error.
	*/
	public function deleteUser($parameters) {
		$user = $parameters['uid'];
		try {
			$stmt = OC_DB::prepare('DELETE FROM *PREFIX*categories WHERE user = ?');
			$result = $stmt->execute(array($user,));
		} catch(Exception $e) {
			OC_Log::write('core','OC_VCategories::deleteFromUser, exception: '.$e->getMessage(), OC_Log::ERROR);
			OC_Log::write('core','OC_VCategories::deleteFromUser, user: '.$user, OC_Log::ERROR);
			return false;
		}
		return true;
	}

	// case-insensitive in_array
	private function in_arrayi($needle, $haystack) {
		return in_array(strtolower($needle), array_map('strtolower', $haystack));
	}

	// case-insensitive array_search
    private function array_searchi($needle, $haystack) {
		return array_search(strtolower($needle),array_map('strtolower',$haystack)); 
	}
}
?>