<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Profile;

use OCP\IUser;

/**
 * @since 23.0.0
 */
interface ILinkAction {
	/**
	 * Preload the user specific value required by the action
	 *
	 * e.g. the email is loaded for the email action and the userId for the Talk action
	 *
	 * @since 23.0.0
	 */
	public function preload(IUser $targetUser): void;

	/**
	 * Returns the app ID of the action
	 *
	 * e.g. 'spreed'
	 *
	 * @since 23.0.0
	 */
	public function getAppId(): string;

	/**
	 * Returns the unique ID of the action
	 *
	 * *For account properties this is the constant defined in lib/public/Accounts/IAccountManager.php*
	 *
	 * e.g. 'email'
	 *
	 * @since 23.0.0
	 */
	public function getId(): string;

	/**
	 * Returns the translated unique display ID of the action
	 *
	 * Should be something short and descriptive of the action
	 * as this is seen by the end-user when configuring actions
	 *
	 * e.g. 'Email'
	 *
	 * @since 23.0.0
	 */
	public function getDisplayId(): string;

	/**
	 * Returns the translated title
	 *
	 * e.g. 'Mail user@domain.com'
	 *
	 * Use the L10N service to translate it
	 *
	 * @since 23.0.0
	 */
	public function getTitle(): string;

	/**
	 * Returns the priority
	 *
	 * *Actions are sorted in ascending order*
	 *
	 * e.g. 60
	 *
	 * @since 23.0.0
	 */
	public function getPriority(): int;

	/**
	 * Returns the URL link to the 16*16 SVG icon
	 *
	 * @since 23.0.0
	 */
	public function getIcon(): string;

	/**
	 * Returns the target of the action,
	 * if null is returned the action won't be registered
	 *
	 * e.g. 'mailto:user@domain.com'
	 *
	 * @since 23.0.0
	 */
	public function getTarget(): ?string;
}
