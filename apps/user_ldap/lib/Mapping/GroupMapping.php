<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
	protected function getTableName(bool $includePrefix = true) {
		$p = $includePrefix ? '*PREFIX*' : '';
		return $p . 'ldap_group_mapping';
	}
}
