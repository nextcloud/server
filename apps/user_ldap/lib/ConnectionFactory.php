<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP;

class ConnectionFactory {
	/** @var ILDAPWrapper */
	private $ldap;

	public function __construct(ILDAPWrapper $ldap) {
		$this->ldap = $ldap;
	}

	public function get($prefix) {
		return new Connection($this->ldap, $prefix, 'user_ldap');
	}
}
