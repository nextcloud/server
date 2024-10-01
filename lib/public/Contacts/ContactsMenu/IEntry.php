<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Contacts\ContactsMenu;

use JsonSerializable;

/**
 * @since 12.0
 */
interface IEntry extends JsonSerializable {
	/**
	 * @since 12.0
	 * @return string
	 */
	public function getFullName(): string;

	/**
	 * @since 12.0
	 * @return string[]
	 */
	public function getEMailAddresses(): array;

	/**
	 * @since 12.0
	 * @return string|null image URI
	 */
	public function getAvatar(): ?string;

	/**
	 * @since 12.0
	 * @param IAction $action an action to show in the contacts menu
	 */
	public function addAction(IAction $action): void;

	/**
	 * Set the (system) contact's user status
	 *
	 * @since 28.0
	 * @param string $status
	 * @param string $statusMessage
	 * @param string|null $icon
	 * @return void
	 */
	public function setStatus(string $status,
		?string $statusMessage = null,
		?int $statusMessageTimestamp = null,
		?string $icon = null): void;

	/**
	 * Get an arbitrary property from the contact
	 *
	 * @since 12.0
	 * @param string $key
	 * @return mixed the value of the property or null
	 */
	public function getProperty(string $key);
}
