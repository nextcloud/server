<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_Contacts_Share extends OCP\Share_Backend {
	const FORMAT_ADDRESSBOOKS = 1;
	/**
	* @brief Get the source of the item to be stored in the database
	* @param string Item
	* @param string Owner of the item
	* @return mixed|array|false Source
	*
	* Return an array if the item is file dependent, the array needs two keys: 'item' and 'file'
	* Return false if the item does not exist for the user
	*
	* The formatItems() function will translate the source returned back into the item
	*/
	public function getSource($item, $uid) {
		$addressbook = OC_Contacts_Addressbook::find( $item );
		if( $addressbook === false || $addressbook['userid'] != $uid) {
			return false;
		}
		return $item;
	}

	/**
	* @brief Get a unique name of the item for the specified user
	* @param string Item
	* @param string|false User the item is being shared with
	* @param array|null List of similar item names already existing as shared items
	* @return string Target name
	*
	* This function needs to verify that the user does not already have an item with this name.
	* If it does generate a new name e.g. name_#
	*/
	public function generateTarget($item, $uid, $exclude = null) {
		$addressbook = OC_Contacts_Addressbook::find( $item );
		$user_addressbooks = array();
		foreach(OC_Contacts_Addressbook::all($uid) as $user_addressbook) {
			$user_addressbooks[] = $user_addressbook['displayname'];
		}
		$name = $addressbook['userid']."'s ".$addressbook['displayname'];
		$suffix = '';
		while (in_array($name.$suffix, $user_addressbooks)) {
			$suffix++;
		}
		
		return $name.$suffix;
	}

	/**
	* @brief Converts the shared item sources back into the item in the specified format
	* @param array Sources of shared items
	* @param int Format 
	* @return ?
	* 
	* The items array is formatted with the sources as the keys to an array with the following keys: item_target, permissions, stime
	* This function allows the backend to control the output of shared items with custom formats.
	* It is only called through calls to the public getItem(s)SharedWith functions.
	*/
	public function formatItems($items, $format) {
		$addressbooks = array();
		foreach($items as $source => $info) {
			$addressbook = OC_Contacts_Addressbook::find( $source );
			$addressbook['displayname'] = $info['item_target'];
			$addressbooks[] = $addressbook;
		}
		return $addressbooks;
	}
}
