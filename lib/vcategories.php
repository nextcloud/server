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
	const PREF_CATEGORIES_LABEL = 'extra categories';
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
	*/
	public function __construct($app, $user=null) {
		$this->app = $app;
		$this->user = is_null($user) ? OC_User::getUser() : $user;
		$this->categories = OC_VObject::unescapeSemicolons(OC_Preferences::getValue($this->user, $app, self::PREF_CATEGORIES_LABEL, ''));
	}

	/**
	* @brief Get the categories for a specific user.
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
		return $this->in_arrayi($name, $this->categories);
	}

	/**
	* @brief Add a new category name.
	* @param $names A string with a name or an array of strings containing
	* the name(s) of the categor(y|ies) to add.
	* @param $sync bool When true, save the categories
	* @returns bool Returns false on error.
	*/
	public function add($names, $sync=true) {
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
			$this->categories = $this->cleanArray(array_merge($this->categories, $newones));
			natcasesort($this->categories); // Dunno if this is necessary
			if($sync) {
				$this->save();
			}
		}
		return true;
	}

	/**
	* @brief Extracts categories from a vobject and add the ones not already present.
	* @param $vobject The instance of OC_VObject to load the categories from.
	*/
	public function loadFromVObject($vobject, $sync=true) {
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
	public function rescan($objects) {
		$this->categories = array();
		foreach($objects as $object) {
			$vobject = OC_VObject::parse($object);
			if(!is_null($vobject)) {
				$this->loadFromVObject($vobject, false);
			} else {
				OC_Log::write('core','OC_VCategories::rescan, unable to parse. ID: '.$value[0].', '.substr($value[1], 0, 20).'(...)', OC_Log::DEBUG);
			}
		}
		$this->save();
	}

	/**
	 * @brief Save the list with categories
	 */
	public function save() {
		$escaped_categories = OC_VObject::escapeSemicolons($this->categories);
		OC_Preferences::setValue($this->user, $this->app, self::PREF_CATEGORIES_LABEL, $escaped_categories);
	}

	/**
	* @brief Delete a category from the db and from all the vobject supplied
	* @param $name
	* @param $objects An array of arrays with [id,vobject] (as text) pairs suitable for updating the apps object table.
	*/
	public function delete($name, array &$objects) {
		if(!$this->hasCategory($name)) {
			return;
		}
		unset($this->categories[$this->array_searchi($name, $this->categories)]);
		$this->categories = $this->cleanArray($this->categories);
		$this->save();
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
				OC_Log::write('core','OC_VCategories::delete, unable to parse. ID: '.$value[0].', '.substr($value[1], 0, 20).'(...)', OC_Log::DEBUG);
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

	/*
	 * this is for a bug in the code, need to check if it is still needed
	 */
	private function cleanArray($array, $remove_null_number = true){
		$new_array = array();
		$null_exceptions = array();

		foreach ($array as $key => $value){
			$value = trim($value);
			if($remove_null_number){
				$null_exceptions[] = '0';
			}
			if(!in_array($value, $null_exceptions) && $value != "")	{
				$new_array[] = $value;
			}
		}
		return $new_array;
	}
}
?>
