<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Contacts\ContactsMenu;

/**
 * @since 12.0
 */
interface IActionFactory {
	/**
	 * Construct and return a new link action for the contacts menu
	 *
	 * @since 12.0
	 *
	 * @param string $icon full path to the action's icon
	 * @param string $name localized name of the action
	 * @param string $href target URL
	 * @param string $appId the app ID registering the action
	 * @return ILinkAction
	 */
	public function newLinkAction(string $icon, string $name, string $href, string $appId = ''): ILinkAction;

	/**
	 * Construct and return a new email action for the contacts menu
	 *
	 * @since 12.0
	 *
	 * @param string $icon full path to the action's icon
	 * @param string $name localized name of the action
	 * @param string $email target e-mail address
	 * @param string $appId the appName registering the action
	 * @return ILinkAction
	 */
	public function newEMailAction(string $icon, string $name, string $email, string $appId = ''): ILinkAction;
}
