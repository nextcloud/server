<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\LDAP;

/**
 * Interface IDeletionFlagSupport
 *
 * @since 11.0.0
 */
interface IDeletionFlagSupport {
	/**
	 * Flag record for deletion.
	 * @param string $uid user id
	 * @since 11.0.0
	 */
	public function flagRecord($uid);
	
	/**
	 * Unflag record for deletion.
	 * @param string $uid user id
	 * @since 11.0.0
	 */
	public function unflagRecord($uid);
}
