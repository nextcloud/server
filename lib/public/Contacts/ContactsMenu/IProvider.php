<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Contacts\ContactsMenu;

/**
 * Process contacts menu entries
 *
 * @see IBulkProvider for providers that work with the full dataset at once
 *
 * @since 12.0
 */
interface IProvider {
	/**
	 * @since 12.0
	 * @param IEntry $entry
	 * @return void
	 */
	public function process(IEntry $entry);
}
