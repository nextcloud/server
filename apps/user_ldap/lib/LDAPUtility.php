<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP;

abstract class LDAPUtility {
	/**
	 * constructor, make sure the subclasses call this one!
	 * @param ILDAPWrapper $ldap an instance of an ILDAPWrapper
	 */
	public function __construct(
		protected ILDAPWrapper $ldap,
	) {
	}
}
