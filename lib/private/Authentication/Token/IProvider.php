<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
		?int $expires = null,
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
	 * Move the session token of $oldSessionId to $sessionId. The new token belongs to the same login.
	 *
	 * @param string $oldSessionId
	 * @param string $sessionId
	 * @throws InvalidTokenException also when a concurrent request renewed or invalidated the token first
	 * @throws \RuntimeException when OpenSSL reports a problem
	 * @return OCPIToken The new token
	 */
	public function renewSessionToken(string $oldSessionId, string $sessionId): OCPIToken;

	/**
	 * Give $sessionId a session token with the login data of the remembered session token of $sourceSessionId,
	 * which stays valid. The new token belongs to the same login.
	 *
	 * @throws InvalidTokenException
	 * @throws \RuntimeException when OpenSSL reports a problem
	 * @return OCPIToken The new token
	 */
	public function copySessionToken(string $sourceSessionId, string $sessionId): OCPIToken;

	/**
	 * Invalidate (delete) the given token and the other tokens of its login
	 *
	 * @param string $token
	 */
	public function invalidateToken(string $token);

	/**
	 * Invalidate (delete) the given token and the other tokens of its login. $id may also be the id of a login
	 * whose first token is gone.
	 *
	 * @param string $uid
	 * @param int $id
	 */
	public function invalidateTokenById(string $uid, int $id);

	/**
	 * Invalidate (delete) the other tokens of the login the given token belongs to
	 *
	 * @throws InvalidTokenException
	 */
	public function invalidateOtherTokensOfLogin(OCPIToken $token): void;

	/**
	 * Get the tokens of a login of $uid, the most recently used first. $loginId may also be the id of any token of
	 * that login.
	 *
	 * A login starts with the token created by logging in. Renewing or copying a session token keeps its login, so
	 * one browser login is one login, whichever session id it currently uses.
	 *
	 * @return list<OCPIToken> Empty when $uid has no such login
	 */
	public function getTokensOfLogin(string $uid, int $loginId): array;

	/**
	 * Get all tokens of a user, grouped by their login
	 *
	 * The provider may limit the number of tokens in case of an abuse
	 * where a high number of (session) tokens is generated
	 *
	 * @return array<int, non-empty-list<OCPIToken>> The tokens of each login, the most recently used first,
	 *                                               keyed by the id of the login
	 */
	public function getLoginsOfUser(string $uid): array;

	/**
	 * Set the name and scope of every token of a login of $uid, also of those a concurrent request is storing.
	 * $loginId may also be the id of any token of that login.
	 *
	 * @param ?string $name The new name, null to keep it
	 * @param ?array $scope The new scope, null to keep it
	 */
	public function updateLogin(string $uid, int $loginId, ?string $name, ?array $scope): void;

	/**
	 * Get the id of the login the given token belongs to
	 *
	 * @throws InvalidTokenException
	 */
	public function getLoginId(OCPIToken $token): int;

	/**
	 * Mark the given token and the other tokens of its login for remote wipe
	 *
	 * @throws InvalidTokenException
	 */
	public function markForWipe(OCPIToken $token): void;

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
	 * Rotate the token. Useful for example oauth tokens
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
