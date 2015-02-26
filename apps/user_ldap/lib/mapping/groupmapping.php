<?php
/**
* Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.com>
* This file is licensed under the Affero General Public License version 3 or
* later.
* See the COPYING-README file.
*/

namespace OCA\User_LDAP\Mapping;

/**
* Class UserMapping
* @package OCA\User_LDAP\Mapping
*/
class GroupMapping extends AbstractMapping {

	/**
	* returns the DB table name which holds the mappings
	* @return string
	*/
	protected function getTableName() {
		return '*PREFIX*ldap_group_mapping';
	}

}
