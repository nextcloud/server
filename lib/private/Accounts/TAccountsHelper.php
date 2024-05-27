<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Accounts;

use OCP\Accounts\IAccountManager;

trait TAccountsHelper {
	/**
	 * returns whether the property is a collection
	 */
	protected function isCollection(string $propertyName): bool {
		return in_array(
			$propertyName,
			[
				IAccountManager::COLLECTION_EMAIL,
			],
			true
		);
	}
}
