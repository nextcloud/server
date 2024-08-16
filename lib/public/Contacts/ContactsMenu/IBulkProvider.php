<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Contacts\ContactsMenu;

/**
 * Process contacts menu entries in bulk
 *
 * @since 28.0
 */
interface IBulkProvider {
	/**
	 * @since 28.0
	 * @param list<IEntry> $entries
	 * @return void
	 */
	public function process(array $entries): void;
}
