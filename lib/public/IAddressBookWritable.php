<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors"
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP;

/**
 * IAddressBook Interface extension for checking if the address book is writable
 *
 * @since 34.0.5
 */
interface IAddressBookWritable extends IAddressBook {
	/**
	 * Indicates whether the address book can be modified
	 *
	 * @since 34.0.5
	 *
	 * @return bool
	 */
	public function isWritable(): bool;
}
