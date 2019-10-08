<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\OAuth2\Service;

use OC\Authentication\Token\IProvider as TokenProvider;
use OC\Authentication\Exceptions\ExpiredTokenException;
use OC\Authentication\Exceptions\InvalidTokenException;
use OCA\OAuth2\Db\AccessToken;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Exceptions\RefreshFailedException;
use OCA\OAuth2\Response\TokenServiceResponse;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;

class TokenService {

	/** @var TokenProvider */
	private $tokenProvider;
	/** @var ICrypto */
	private $crypto;
	/** @var ISecureRandom */
	private $secureRandom;
	/** @var AccessTokenMapper */
	private $accessTokenMapper;

	public function __construct(AccessTokenMapper $accessTokenMapper,
								TokenProvider $tokenProvider,
								ICrypto $crypto,
								ISecureRandom $secureRandom) {
		$this->tokenProvider = $tokenProvider;
		$this->crypto = $crypto;
		$this->secureRandom = $secureRandom;
		$this->accessTokenMapper = $accessTokenMapper;
	}

	/**
	 * @throws RefreshFailedException
	 */
	public function refreshToken(AccessToken $accessToken, string $code): TokenServiceResponse {
		$decryptedToken = $this->crypto->decrypt($accessToken->getEncryptedToken(), $code);

		// Obtain the appToken assoicated
		try {
			$appToken = $this->tokenProvider->getTokenById($accessToken->getTokenId());
		} catch (ExpiredTokenException $e) {
			$appToken = $e->getToken();
		} catch (InvalidTokenException $e) {
			//We can't do anything...
			$this->accessTokenMapper->delete($accessToken);
			throw new RefreshFailedException();
		}

		// Rotate the apptoken (so the old one becomes invalid basically)
		$newToken = $this->secureRandom->generate(72, ISecureRandom::CHAR_UPPER.ISecureRandom::CHAR_LOWER.ISecureRandom::CHAR_DIGITS);

		$appToken = $this->tokenProvider->rotate(
			$appToken,
			$decryptedToken,
			$newToken
		);

		// Expiration is in 1 hour again
		$appToken->setExpires($this->time->getTime() + 3600);
		$this->tokenProvider->updateToken($appToken);

		// Generate a new refresh token and encrypt the new apptoken in the DB
		$newCode = $this->secureRandom->generate(128, ISecureRandom::CHAR_UPPER.ISecureRandom::CHAR_LOWER.ISecureRandom::CHAR_DIGITS);
		$accessToken->setHashedCode(hash('sha512', $newCode));
		$accessToken->setEncryptedToken($this->crypto->encrypt($newToken, $newCode));
		$this->accessTokenMapper->update($accessToken);

		return new TokenServiceResponse($newToken, $newToken, $appToken->getUID());
	}
}
