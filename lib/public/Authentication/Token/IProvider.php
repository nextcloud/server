<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Authentication\Token;

use OCP\Authentication\Exceptions\ExpiredTokenException;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Exceptions\WipeTokenException;

/**
 * @since 24.0.8
 */
interface IProvider {
	/**
	 * invalidates all tokens of a specific user
	 * if a client name is given only tokens of that client will be invalidated
	 *
	 * @param string $uid
	 * @param string|null $clientName
	 * @since 24.0.8
	 * @return void
	 */
	public function invalidateTokensOfUser(string $uid, ?string $clientName);

	/**
	 * Get a token by token string id
	 *
	 * @since 28.0.0
	 * @throws InvalidTokenException
	 * @throws ExpiredTokenException
	 * @throws WipeTokenException
	 * @return IToken
	 */
	public function getToken(string $tokenId): IToken;
}
