<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP;

abstract class BackendUtility {
	/**
	 * constructor, make sure the subclasses call this one!
	 * @param Access $access an instance of Access for LDAP interaction
	 */
	public function __construct(
		protected Access $access,
	) {
	}
}
