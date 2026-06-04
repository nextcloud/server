<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
