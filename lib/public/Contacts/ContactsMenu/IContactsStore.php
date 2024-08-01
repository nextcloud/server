<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Contacts\ContactsMenu;

use OCP\IUser;

/**
 * @since 13.0.0
 */
interface IContactsStore {
	/**
	 * @param IUser $user
	 * @param string|null $filter
	 * @param int|null $limit added 19.0.2
	 * @param int|null $offset added 19.0.2
	 * @return IEntry[]
	 * @since 13.0.0
	 */
	public function getContacts(IUser $user, ?string $filter, ?int $limit = null, ?int $offset = null): array;

	/**
	 * @brief finds a contact by specifying the property to search on ($shareType) and the value ($shareWith)
	 * @since 13.0.0
	 */
	public function findOne(IUser $user, int $shareType, string $shareWith): ?IEntry;
}
