<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Principal;

/**
 * Class Collection
 *
 * @package OCA\DAV\CalDAV\Principal
 */
class Collection extends \Sabre\CalDAV\Principal\Collection {

	/**
	 * Returns a child object based on principal information
	 *
	 * @param array $principalInfo
	 * @return User
	 */
	public function getChildForPrincipal(array $principalInfo) {
		return new User($this->principalBackend, $principalInfo);
	}
}
