<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\OAuth2\Controller;

use OC\Authentication\Token\DefaultTokenMapper;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;

class OauthApiController extends Controller {
	/** @var AccessTokenMapper */
	private $accessTokenMapper;
	/** @var ICrypto */
	private $crypto;
	/** @var DefaultTokenMapper */
	private $defaultTokenMapper;
	/** @var ISecureRandom */
	private $secureRandom;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param ICrypto $crypto
	 * @param AccessTokenMapper $accessTokenMapper
	 * @param DefaultTokenMapper $defaultTokenMapper
	 * @param ISecureRandom $secureRandom
	 */
	public function __construct($appName,
								IRequest $request,
								ICrypto $crypto,
								AccessTokenMapper $accessTokenMapper,
								DefaultTokenMapper $defaultTokenMapper,
								ISecureRandom $secureRandom) {
		parent::__construct($appName, $request);
		$this->crypto = $crypto;
		$this->accessTokenMapper = $accessTokenMapper;
		$this->defaultTokenMapper = $defaultTokenMapper;
		$this->secureRandom = $secureRandom;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $code
	 * @return JSONResponse
	 */
	public function getToken($code) {
		$accessToken = $this->accessTokenMapper->getByCode($code);
		$decryptedToken = $this->crypto->decrypt($accessToken->getEncryptedToken(), $code);
		$newCode = $this->secureRandom->generate(128);
		$accessToken->setHashedCode(hash('sha512', $newCode));
		$accessToken->setEncryptedToken($this->crypto->encrypt($decryptedToken, $newCode));
		$this->accessTokenMapper->update($accessToken);

		return new JSONResponse(
			[
				'access_token' => $decryptedToken,
				'token_type' => 'Bearer',
				'expires_in' => 3600,
				'refresh_token' => $newCode,
				'user_id' => $this->defaultTokenMapper->getTokenById($accessToken->getTokenId())->getUID(),
			]
		);
	}
}
