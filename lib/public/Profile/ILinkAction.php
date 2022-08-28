<?php

declare(strict_types=1);

/**
 * @copyright 2021 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
