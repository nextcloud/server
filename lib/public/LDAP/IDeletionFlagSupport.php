<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\LDAP;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Interface IDeletionFlagSupport
 *
 * @since 11.0.0
 */
#[Consumable(since: '11.0.0')]
interface IDeletionFlagSupport {
	/**
	 * Flag record for deletion.
	 * @param string $uid user id
	 * @since 11.0.0
	 */
	public function flagRecord(string $uid): void;

	/**
	 * Unflag record for deletion.
	 * @param string $uid user id
	 * @since 11.0.0
	 */
	public function unflagRecord(string $uid): void;
}
