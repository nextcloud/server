<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Principal;

/**
 * Class User
 *
 * @package OCA\DAV\CalDAV\Principal
 */
class User extends \Sabre\CalDAV\Principal\User {

	/**
	 * Returns a list of ACE's for this node.
	 *
	 * Each ACE has the following properties:
	 *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
	 *     currently the only supported privileges
	 *   * 'principal', a url to the principal who owns the node
	 *   * 'protected' (optional), indicating that this ACE is not allowed to
	 *      be updated.
	 *
	 * @return array
	 */
	public function getACL() {
		$acl = parent::getACL();
		$acl[] = [
			'privilege' => '{DAV:}read',
			'principal' => '{DAV:}authenticated',
			'protected' => true,
		];
		return $acl;
	}
}
