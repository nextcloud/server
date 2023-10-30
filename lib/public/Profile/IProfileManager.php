<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Profile;

use OCP\Accounts\IAccountManager;
use OCP\IUser;

/**
 * @since 28.0.0
 */
interface IProfileManager {
	/**
	 * Visible to users, guests, and public access
	 *
	 * @since 28.0.0
	 */
	public const VISIBILITY_SHOW = 'show';

	/**
	 * Visible to users and guests
	 *
	 * @since 28.0.0
	 */
	public const VISIBILITY_SHOW_USERS_ONLY = 'show_users_only';

	/**
	 * Visible to nobody
	 *
	 * @since 28.0.0
	 */
	public const VISIBILITY_HIDE = 'hide';

	/**
	 * Default account property visibility
	 *
	 * @since 28.0.0
	 */
	public const DEFAULT_PROPERTY_VISIBILITY = [
		IAccountManager::PROPERTY_ADDRESS => self::VISIBILITY_SHOW_USERS_ONLY,
		IAccountManager::PROPERTY_AVATAR => self::VISIBILITY_SHOW,
		IAccountManager::PROPERTY_BIOGRAPHY => self::VISIBILITY_SHOW,
		IAccountManager::PROPERTY_DISPLAYNAME => self::VISIBILITY_SHOW,
		IAccountManager::PROPERTY_HEADLINE => self::VISIBILITY_SHOW,
		IAccountManager::PROPERTY_ORGANISATION => self::VISIBILITY_SHOW,
		IAccountManager::PROPERTY_ROLE => self::VISIBILITY_SHOW,
		IAccountManager::PROPERTY_EMAIL => self::VISIBILITY_SHOW_USERS_ONLY,
		IAccountManager::PROPERTY_PHONE => self::VISIBILITY_SHOW_USERS_ONLY,
		IAccountManager::PROPERTY_TWITTER => self::VISIBILITY_SHOW,
		IAccountManager::PROPERTY_WEBSITE => self::VISIBILITY_SHOW,
	];

	/**
	 * Default visibility
	 *
	 * @since 28.0.0
	 */
	public const DEFAULT_VISIBILITY = self::VISIBILITY_SHOW_USERS_ONLY;

	/**
	 * If no user is passed as an argument return whether profile is enabled globally in `config.php`
	 *
	 * @since 28.0.0
	 */
	public function isProfileEnabled(?IUser $user = null): bool;

	/**
	 * Return whether the profile parameter of the target user
	 * is visible to the visiting user
	 *
	 * @since 28.0.0
	 */
	public function isProfileFieldVisible(string $profileField, IUser $targetUser, ?IUser $visitingUser): bool;

	/**
	 * Return the profile parameters of the target user that are visible to the visiting user
	 * in an associative array
	 *
	 * @return array{userId: string, address?: ?string, biography?: ?string, displayname?: ?string, headline?: ?string, isUserAvatarVisible?: bool, organisation?: ?string, role?: ?string, actions: list<array{id: string, icon: string, title: string, target: ?string}>}
	 * @since 28.0.0
	 */
	public function getProfileFields(IUser $targetUser, ?IUser $visitingUser): array;
}
