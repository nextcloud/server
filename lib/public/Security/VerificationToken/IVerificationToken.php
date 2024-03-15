<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Security\VerificationToken;

use OCP\IUser;

/**
 * @since 23.0.0
 */
interface IVerificationToken {
	/**
	 * Checks whether the a provided tokent matches a stored token and its
	 * constraints. An InvalidTokenException is thrown on issues, otherwise
	 * the check is successful.
	 *
	 * null can be passed as $user, but mind that this is for conveniently
	 * passing the return of IUserManager::getUser() to this method. When
	 * $user is null, InvalidTokenException is thrown for all the issued
	 * tokens are user related.
	 *
	 * @throws InvalidTokenException
	 * @since 23.0.0
	 */
	public function check(string $token, ?IUser $user, string $subject, string $passwordPrefix = '', bool $expiresWithLogin = false): void;

	/**
	 * @since 23.0.0
	 */
	public function create(IUser $user, string $subject, string $passwordPrefix = ''): string;

	/**
	 * Deletes the token identified by the provided parameters
	 *
	 * @since 23.0.0
	 */
	public function delete(string $token, IUser $user, string $subject): void;
}
