<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Marcel Waldvogel <marcel.waldvogel@uni-konstanz.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Authentication\Token;

use OC\Authentication\Exceptions\PasswordlessTokenException;
use OCP\Authentication\Exceptions\ExpiredTokenException;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Exceptions\WipeTokenException;
use OCP\Authentication\Token\IToken as OCPIToken;

interface IProvider {
	/**
	 * Create and persist a new token
	 *
	 * @param string $token
	 * @param string $uid
	 * @param string $loginName
	 * @param string|null $password
	 * @param string $name Name will be trimmed to 120 chars when longer
	 * @param int $type token type
	 * @param int $remember whether the session token should be used for remember-me
	 * @return OCPIToken
	 * @throws \RuntimeException when OpenSSL reports a problem
	 */
	public function generateToken(string $token,
		string $uid,
		string $loginName,
		?string $password,
		string $name,
		int $type = OCPIToken::TEMPORARY_TOKEN,
		int $remember = OCPIToken::DO_NOT_REMEMBER,
		?array $scope = null,
	): OCPIToken;

	/**
	 * Get a token by token id
	 *
	 * @param string $tokenId
	 * @throws InvalidTokenException
	 * @throws ExpiredTokenException
	 * @throws WipeTokenException
	 * @return OCPIToken
	 */
	public function getToken(string $tokenId): OCPIToken;

	/**
	 * Get a token by token id
	 *
	 * @param int $tokenId
	 * @throws InvalidTokenException
	 * @throws ExpiredTokenException
	 * @throws WipeTokenException
	 * @return OCPIToken
	 */
	public function getTokenById(int $tokenId): OCPIToken;

	/**
	 * Duplicate an existing session token
	 *
	 * @param string $oldSessionId
	 * @param string $sessionId
	 * @throws InvalidTokenException
	 * @throws \RuntimeException when OpenSSL reports a problem
	 * @return OCPIToken The new token
	 */
	public function renewSessionToken(string $oldSessionId, string $sessionId): OCPIToken;

	/**
	 * Invalidate (delete) the given session token
	 *
	 * @param string $token
	 */
	public function invalidateToken(string $token);

	/**
	 * Invalidate (delete) the given token
	 *
	 * @param string $uid
	 * @param int $id
	 */
	public function invalidateTokenById(string $uid, int $id);

	/**
	 * Invalidate (delete) old session tokens
	 */
	public function invalidateOldTokens();

	/**
	 * Invalidate (delete) tokens last used before a given date
	 */
	public function invalidateLastUsedBefore(string $uid, int $before): void;

	/**
	 * Save the updated token
	 *
	 * @param OCPIToken $token
	 */
	public function updateToken(OCPIToken $token);

	/**
	 * Update token activity timestamp
	 *
	 * @param OCPIToken $token
	 */
	public function updateTokenActivity(OCPIToken $token);

	/**
	 * Get all tokens of a user
	 *
	 * The provider may limit the number of result rows in case of an abuse
	 * where a high number of (session) tokens is generated
	 *
	 * @param string $uid
	 * @return OCPIToken[]
	 */
	public function getTokenByUser(string $uid): array;

	/**
	 * Get the (unencrypted) password of the given token
	 *
	 * @param OCPIToken $savedToken
	 * @param string $tokenId
	 * @throws InvalidTokenException
	 * @throws PasswordlessTokenException
	 * @return string
	 */
	public function getPassword(OCPIToken $savedToken, string $tokenId): string;

	/**
	 * Encrypt and set the password of the given token
	 *
	 * @param OCPIToken $token
	 * @param string $tokenId
	 * @param string $password
	 * @throws InvalidTokenException
	 */
	public function setPassword(OCPIToken $token, string $tokenId, string $password);

	/**
	 * Rotate the token. Useful for for example oauth tokens
	 *
	 * @param OCPIToken $token
	 * @param string $oldTokenId
	 * @param string $newTokenId
	 * @return OCPIToken
	 * @throws \RuntimeException when OpenSSL reports a problem
	 */
	public function rotate(OCPIToken $token, string $oldTokenId, string $newTokenId): OCPIToken;

	/**
	 * Marks a token as having an invalid password.
	 *
	 * @param OCPIToken $token
	 * @param string $tokenId
	 */
	public function markPasswordInvalid(OCPIToken $token, string $tokenId);

	/**
	 * Update all the passwords of $uid if required
	 *
	 * @param string $uid
	 * @param string $password
	 */
	public function updatePasswords(string $uid, string $password);
}
