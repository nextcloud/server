<?php

declare(strict_types=1);

/**
 * @copyright Copyright 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OC\Authentication\Token;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OC\Authentication\Exceptions\ExpiredTokenException;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Exceptions\PasswordlessTokenException;
use OC\Authentication\Exceptions\WipeTokenException;
use OCP\Authentication\Token\IProvider as OCPIProvider;

class Manager implements IProvider, OCPIProvider {
	/** @var PublicKeyTokenProvider */
	private $publicKeyTokenProvider;

	public function __construct(PublicKeyTokenProvider $publicKeyTokenProvider) {
		$this->publicKeyTokenProvider = $publicKeyTokenProvider;
	}

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
	 * @return IToken
	 */
	public function generateToken(string $token,
								  string $uid,
								  string $loginName,
								  $password,
								  string $name,
								  int $type = IToken::TEMPORARY_TOKEN,
								  int $remember = IToken::DO_NOT_REMEMBER): IToken {
		if (mb_strlen($name) > 128) {
			$name = mb_substr($name, 0, 120) . '…';
		}

		try {
			return $this->publicKeyTokenProvider->generateToken(
				$token,
				$uid,
				$loginName,
				$password,
				$name,
				$type,
				$remember
			);
		} catch (UniqueConstraintViolationException $e) {
			// It's rare, but if two requests of the same session (e.g. env-based SAML)
			// try to create the session token they might end up here at the same time
			// because we use the session ID as token and the db token is created anew
			// with every request.
			//
			// If the UIDs match, then this should be fine.
			$existing = $this->getToken($token);
			if ($existing->getUID() !== $uid) {
				throw new \Exception('Token conflict handled, but UIDs do not match. This should not happen', 0, $e);
			}
			return $existing;
		}
	}

	/**
	 * Save the updated token
	 *
	 * @param IToken $token
	 * @throws InvalidTokenException
	 */
	public function updateToken(IToken $token) {
		$provider = $this->getProvider($token);
		$provider->updateToken($token);
	}

	/**
	 * Update token activity timestamp
	 *
	 * @throws InvalidTokenException
	 * @param IToken $token
	 */
	public function updateTokenActivity(IToken $token) {
		$provider = $this->getProvider($token);
		$provider->updateTokenActivity($token);
	}

	/**
	 * @param string $uid
	 * @return IToken[]
	 */
	public function getTokenByUser(string $uid): array {
		return $this->publicKeyTokenProvider->getTokenByUser($uid);
	}

	/**
	 * Get a token by token
	 *
	 * @param string $tokenId
	 * @throws InvalidTokenException
	 * @throws \RuntimeException when OpenSSL reports a problem
	 * @return IToken
	 */
	public function getToken(string $tokenId): IToken {
		try {
			return $this->publicKeyTokenProvider->getToken($tokenId);
		} catch (WipeTokenException $e) {
			throw $e;
		} catch (ExpiredTokenException $e) {
			throw $e;
		} catch (InvalidTokenException $e) {
			throw $e;
		}
	}

	/**
	 * Get a token by token id
	 *
	 * @param int $tokenId
	 * @throws InvalidTokenException
	 * @return IToken
	 */
	public function getTokenById(int $tokenId): IToken {
		try {
			return $this->publicKeyTokenProvider->getTokenById($tokenId);
		} catch (ExpiredTokenException $e) {
			throw $e;
		} catch (WipeTokenException $e) {
			throw $e;
		} catch (InvalidTokenException $e) {
			throw $e;
		}
	}

	/**
	 * @param string $oldSessionId
	 * @param string $sessionId
	 * @throws InvalidTokenException
	 * @return IToken
	 */
	public function renewSessionToken(string $oldSessionId, string $sessionId): IToken {
		try {
			return $this->publicKeyTokenProvider->renewSessionToken($oldSessionId, $sessionId);
		} catch (ExpiredTokenException $e) {
			throw $e;
		} catch (InvalidTokenException $e) {
			throw $e;
		}
	}

	/**
	 * @param IToken $savedToken
	 * @param string $tokenId session token
	 * @throws InvalidTokenException
	 * @throws PasswordlessTokenException
	 * @return string
	 */
	public function getPassword(IToken $savedToken, string $tokenId): string {
		$provider = $this->getProvider($savedToken);
		return $provider->getPassword($savedToken, $tokenId);
	}

	public function setPassword(IToken $token, string $tokenId, string $password) {
		$provider = $this->getProvider($token);
		$provider->setPassword($token, $tokenId, $password);
	}

	public function invalidateToken(string $token) {
		$this->publicKeyTokenProvider->invalidateToken($token);
	}

	public function invalidateTokenById(string $uid, int $id) {
		$this->publicKeyTokenProvider->invalidateTokenById($uid, $id);
	}

	public function invalidateOldTokens() {
		$this->publicKeyTokenProvider->invalidateOldTokens();
	}

	/**
	 * @param IToken $token
	 * @param string $oldTokenId
	 * @param string $newTokenId
	 * @return IToken
	 * @throws InvalidTokenException
	 * @throws \RuntimeException when OpenSSL reports a problem
	 */
	public function rotate(IToken $token, string $oldTokenId, string $newTokenId): IToken {
		if ($token instanceof PublicKeyToken) {
			return $this->publicKeyTokenProvider->rotate($token, $oldTokenId, $newTokenId);
		}

		throw new InvalidTokenException();
	}

	/**
	 * @param IToken $token
	 * @return IProvider
	 * @throws InvalidTokenException
	 */
	private function getProvider(IToken $token): IProvider {
		if ($token instanceof PublicKeyToken) {
			return $this->publicKeyTokenProvider;
		}
		throw new InvalidTokenException();
	}


	public function markPasswordInvalid(IToken $token, string $tokenId) {
		$this->getProvider($token)->markPasswordInvalid($token, $tokenId);
	}

	public function updatePasswords(string $uid, string $password) {
		$this->publicKeyTokenProvider->updatePasswords($uid, $password);
	}

	public function invalidateTokensOfUser(string $uid, ?string $clientName) {
		$tokens = $this->getTokenByUser($uid);
		foreach ($tokens as $token) {
			if ($clientName === null || ($token->getName() === $clientName)) {
				$this->invalidateTokenById($uid, $token->getId());
			}
		}
	}
}
