<?php
declare(strict_types=1);
/**
 * @copyright Copyright 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Authentication\Token;

use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Exceptions\PasswordlessTokenException;
use OCP\IUser;

class Manager implements IProvider {

	/** @var DefaultTokenProvider */
	private $defaultTokenProvider;

	public function __construct(DefaultTokenProvider $defaultTokenProvider) {
		$this->defaultTokenProvider = $defaultTokenProvider;
	}

	/**
	 * Create and persist a new token
	 *
	 * @param string $token
	 * @param string $uid
	 * @param string $loginName
	 * @param string|null $password
	 * @param string $name
	 * @param int $type token type
	 * @param int $remember whether the session token should be used for remember-me
	 * @return IToken
	 */
	public function generateToken(string $token,
								  string $uid,
								  string $loginName,
								  $password,
								  string $name,
								  int $type = IToken::TEMPORARY_TOKEN,
								  int $remember = IToken::DO_NOT_REMEMBER): IToken {
		//TODO switch to new token by default once it is there
		return $this->defaultTokenProvider->generateToken(
			$token,
			$uid,
			$loginName,
			$password,
			$name,
			$type,
			$remember
		);
	}

	/**
	 * Save the updated token
	 *
	 * @param IToken $token
	 * @throws InvalidTokenException
	 */
	public function updateToken(IToken $token) {
		if ($token instanceof DefaultToken) {
			$this->defaultTokenProvider->updateToken($token);
		} else {
			throw new InvalidTokenException();
		}
	}

	/**
	 * Update token activity timestamp
	 *
	 * @throws InvalidTokenException
	 * @param IToken $token
	 */
	public function updateTokenActivity(IToken $token) {
		if ($token instanceof DefaultToken) {
			$this->defaultTokenProvider->updateTokenActivity($token);
		} else {
			throw new InvalidTokenException();
		}
	}

	/**
	 * Get all tokens of a user
	 *
	 * The provider may limit the number of result rows in case of an abuse
	 * where a high number of (session) tokens is generated
	 *
	 * @param IUser $user
	 * @return IToken[]
	 */
	public function getTokenByUser(string $uid): array {
		return $this->defaultTokenProvider->getTokenByUser($uid);
	}

	/**
	 * Get a token by token
	 *
	 * @param string $tokenId
	 * @throws InvalidTokenException
	 * @return IToken
	 */
	public function getToken(string $tokenId): IToken {
		// TODO: first try new token then old token
		return $this->defaultTokenProvider->getToken($tokenId);
	}

	/**
	 * Get a token by token id
	 *
	 * @param int $tokenId
	 * @throws InvalidTokenException
	 * @return IToken
	 */
	public function getTokenById(int $tokenId): IToken {
		// TODO: Find a way to distinguis between tokens
		return $this->defaultTokenProvider->getTokenById($tokenId);
	}

	/**
	 * @param string $oldSessionId
	 * @param string $sessionId
	 * @throws InvalidTokenException
	 */
	public function renewSessionToken(string $oldSessionId, string $sessionId) {
		// TODO: first try new then old
		// TODO: if old move to new token type
		$this->defaultTokenProvider->renewSessionToken($oldSessionId, $sessionId);
	}

	/**
	 * @param IToken $savedToken
	 * @param string $tokenId session token
	 * @throws InvalidTokenException
	 * @throws PasswordlessTokenException
	 * @return string
	 */
	public function getPassword(IToken $savedToken, string $tokenId): string {
		//TODO convert to new token type
		if ($savedToken instanceof DefaultToken) {
			return $this->defaultTokenProvider->getPassword($savedToken, $tokenId);
		}
	}

	/**
	 * Encrypt and set the password of the given token
	 *
	 * @param IToken $token
	 * @param string $tokenId
	 * @param string $password
	 * @throws InvalidTokenException
	 */
	public function setPassword(IToken $token, string $tokenId, string $password) {
		//TODO conver to new token
		if ($token instanceof DefaultToken) {
			$this->defaultTokenProvider->setPassword($token, $tokenId, $password);
		}
	}

	/**
	 * Invalidate (delete) the given session token
	 *
	 * @param string $token
	 */
	public function invalidateToken(string $token) {
		// TODO: check both providers
		$this->defaultTokenProvider->invalidateToken($token);
	}

	/**
	 * Invalidate (delete) the given token
	 *
	 * @param IUser $user
	 * @param int $id
	 */
	public function invalidateTokenById(string $uid, int $id) {
		//TODO find way to distinguis between tokens
		$this->defaultTokenProvider->invalidateTokenById($uid, $id);
	}

	/**
	 * Invalidate (delete) old session tokens
	 */
	public function invalidateOldTokens() {
		//Call on both
		$this->defaultTokenProvider->invalidateOldTokens();
	}

	public function rotate(IToken $token, string $oldTokenId, string $newTokenId): IToken {
		// Migrate to new token
		return $this->defaultTokenProvider->rotate($token, $oldTokenId, $newTokenId);
	}


}
