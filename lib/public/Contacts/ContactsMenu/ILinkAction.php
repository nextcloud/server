<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Contacts\ContactsMenu;

/**
 * @since 12.0
 */
interface ILinkAction extends IAction {
	/**
	 * @param string $href the target URL of the action
	 * @since 12.0
	 */
	public function setHref(string $href);

	/**
	 * @since 12.0
	 * @return string
	 */
	public function getHref(): string;
}
