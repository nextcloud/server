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


/**
 * Class for easy access to categories in VCARD, VEVENT, VTODO and VJOURNAL.
 * A Category can be e.g. 'Family', 'Work', 'Chore', 'Special Occation' or
 * anything else that is either parsed from a vobject or that the user chooses
 * to add.
 * Category names are not case-sensitive, but will be saved with the case they
 * are entered in. If a user already has a category 'family' for an app, and
 * tries to add a category named 'Family' it will be silently ignored.
 * NOTE: There is a limitation in that the the configvalue field in the
 * preferences table is a varchar(255).
 */
class OC_VCategories {
	const PREF_CATEGORIES_LABEL = 'extra_categories';
	/**
	 * Categories
	 */
	private $categories = array();

	private $app = null;
	private $user = null;

	/**
	* @brief Constructor.
	* @param $app The application identifier e.g. 'contacts' or 'calendar'.
	* @param $user The user whos data the object will operate on. This
	*   parameter should normally be omitted but to make an app able to
	*   update categories for all users it is made possible to provide it.
	* @param $defcategories An array of default categories to be used if none is stored.
	*/
	public function __construct($app, $user=null, $defcategories=array()) {
		$this->app = $app;
		$this->user = is_null($user) ? OC_User::getUser() : $user;
		$categories = trim(OC_Preferences::getValue($this->user, $app, self::PREF_CATEGORIES_LABEL, ''));
		$this->categories = $categories != '' ? unserialize($categories) : $defcategories;
	}

	/**
	* @brief Get the categories for a specific user.
	* @returns array containing the categories as strings.
	*/
	public function categories() {
		//OC_Log::write('core','OC_VCategories::categories: '.print_r($this->categories, true), OC_Log::DEBUG);
		usort($this->categories, 'strnatcasecmp'); // usort to also renumber the keys
		return $this->categories;
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
	* @brief Add a new category name.
	* @param $names A string with a name or an array of strings containing
	* the name(s) of the categor(y|ies) to add.
	* @param $sync bool When true, save the categories
	* @returns bool Returns false on error.
	*/
	public function add($names, $sync=false) {
		if(!is_array($names)) {
			$names = array($names);
		}
		$names = array_map('trim', $names);
		$newones = array();
		foreach($names as $name) {
			if(($this->in_arrayi($name, $this->categories) == false) && $name != '') {
				$newones[] = $name;
			}
		}
		if(count($newones) > 0) {
			$this->categories = array_merge($this->categories, $newones);
			if($sync === true) {
				$this->save();
			}
		}
		return true;
	}

	/**
	* @brief Extracts categories from a vobject and add the ones not already present.
	* @param $vobject The instance of OC_VObject to load the categories from.
	*/
	public function loadFromVObject($vobject, $sync=false) {
		$this->add($vobject->getAsArray('CATEGORIES'), $sync);
	}

	/**
	* @brief Reset saved categories and rescan supplied vobjects for categories.
	* @param $objects An array of vobjects (as text).
	* To get the object array, do something like:
	*	// For Addressbook:
	*	$categories = new OC_VCategories('contacts');
	*	$stmt = OC_DB::prepare( 'SELECT carddata FROM *PREFIX*contacts_cards' );
	*	$result = $stmt->execute();
	*	$objects = array();
	*	if(!is_null($result)) {
	*		while( $row = $result->fetchRow()){
	*			$objects[] = $row['carddata'];
	*		}
	*	}
	* 	$categories->rescan($objects);
	*/
	public function rescan($objects, $sync=true) {
		$this->categories = array();
		foreach($objects as $object) {
			//OC_Log::write('core','OC_VCategories::rescan: '.substr($object, 0, 100).'(...)', OC_Log::DEBUG);
			$vobject = OC_VObject::parse($object);
			if(!is_null($vobject)) {
				$this->loadFromVObject($vobject, $sync);
				unset($vobject);
			} else {
				OC_Log::write('core','OC_VCategories::rescan, unable to parse. ID: '.', '.substr($object, 0, 100).'(...)', OC_Log::DEBUG);				
			}
		}
		$this->save();
	}

	/**
	 * @brief Save the list with categories
	 */
	private function save() {
		if(is_array($this->categories)) {
			usort($this->categories, 'strnatcasecmp'); // usort to also renumber the keys
			$escaped_categories = serialize($this->categories);
			OC_Preferences::setValue($this->user, $this->app, self::PREF_CATEGORIES_LABEL, $escaped_categories);
			OC_Log::write('core','OC_VCategories::save: '.print_r($this->categories, true), OC_Log::DEBUG);
		} else {
			OC_Log::write('core','OC_VCategories::save: $this->categories is not an array! '.print_r($this->categories, true), OC_Log::ERROR);
		}
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
		OC_Log::write('core','OC_VCategories::delete, before: '.print_r($this->categories, true), OC_Log::DEBUG);
		foreach($names as $name) {
			OC_Log::write('core','OC_VCategories::delete: '.$name, OC_Log::DEBUG);
			if($this->hasCategory($name)) {
				//OC_Log::write('core','OC_VCategories::delete: '.$name.' got it', OC_Log::DEBUG);
				unset($this->categories[$this->array_searchi($name, $this->categories)]);
			}
		}
		$this->save();
		OC_Log::write('core','OC_VCategories::delete, after: '.print_r($this->categories, true), OC_Log::DEBUG);
		if(!is_null($objects)) {
			foreach($objects as $key=>&$value) {
				$vobject = OC_VObject::parse($value[1]);
				if(!is_null($vobject)){
					$categories = $vobject->getAsArray('CATEGORIES');
					//OC_Log::write('core','OC_VCategories::delete, before: '.$key.': '.print_r($categories, true), OC_Log::DEBUG);
					foreach($names as $name) {
						$idx = $this->array_searchi($name, $categories);
						//OC_Log::write('core','OC_VCategories::delete, loop: '.$name.', '.print_r($idx, true), OC_Log::DEBUG);
						if($idx !== false) {
							OC_Log::write('core','OC_VCategories::delete, unsetting: '.$categories[$this->array_searchi($name, $categories)], OC_Log::DEBUG);
							unset($categories[$this->array_searchi($name, $categories)]);
							//unset($categories[$idx]);
						}
					}
					//OC_Log::write('core','OC_VCategories::delete, after: '.$key.': '.print_r($categories, true), OC_Log::DEBUG);
					$vobject->setString('CATEGORIES', implode(',', $categories));
					$value[1] = $vobject->serialize();
					$objects[$key] = $value;
				} else {
					OC_Log::write('core','OC_VCategories::delete, unable to parse. ID: '.$value[0].', '.substr($value[1], 0, 50).'(...)', OC_Log::DEBUG);
				}
			}
		}
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
