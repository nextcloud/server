<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Create a new contact in an address book from a serialized vCard.
 *
 * @since 32.0.0
 */
#[Consumable(since: '32.0.0')]
interface ICreateContactFromString extends IAddressBook {
	/**
	 * Create a new contact in this address book.
	 *
	 * @param string $name The name or uri of the vCard to be created. Should have a vcf file extension.
	 * @param string $vcfData The raw, serialized vCard data.
	 *
	 * @since 32.0.0
	 */
	public function createFromString(string $name, string $vcfData): void;
}
