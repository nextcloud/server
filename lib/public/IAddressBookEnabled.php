<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors"
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP;

/**
 * IAddressBook Interface extension for checking if the address book is enabled
 *
 * @since 32.0.0
 */
interface IAddressBookEnabled extends IAddressBook {
	/**
	 * Check if the address book is enabled
	 * @return bool
	 * @since 32.0.0
	 */
	public function isEnabled(): bool;
}
