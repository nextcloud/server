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

namespace OCA\OAuth2\Tests\Controller;

use OC\Authentication\Token\DefaultToken;
use OC\Authentication\Token\DefaultTokenMapper;
use OCA\OAuth2\Controller\OauthApiController;
use OCA\OAuth2\Db\AccessToken;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use Test\TestCase;

class OauthApiControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var ICrypto|\PHPUnit_Framework_MockObject_MockObject */
	private $crypto;
	/** @var AccessTokenMapper|\PHPUnit_Framework_MockObject_MockObject */
	private $accessTokenMapper;
	/** @var DefaultTokenMapper|\PHPUnit_Framework_MockObject_MockObject */
	private $defaultTokenMapper;
	/** @var ISecureRandom|\PHPUnit_Framework_MockObject_MockObject */
	private $secureRandom;
	/** @var OauthApiController */
	private $oauthApiController;

	public function setUp() {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->accessTokenMapper = $this->createMock(AccessTokenMapper::class);
		$this->defaultTokenMapper = $this->createMock(DefaultTokenMapper::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);

		$this->oauthApiController = new OauthApiController(
			'oauth2',
			$this->request,
			$this->crypto,
			$this->accessTokenMapper,
			$this->defaultTokenMapper,
			$this->secureRandom
		);
	}

	public function testGetToken() {
		$accessToken = new AccessToken();
		$accessToken->setEncryptedToken('MyEncryptedToken');
		$accessToken->setTokenId(123);
		$this->accessTokenMapper
			->expects($this->once())
			->method('getByCode')
			->willReturn($accessToken);
		$this->crypto
			->expects($this->once())
			->method('decrypt')
			->with('MyEncryptedToken', 'MySecretCode')
			->willReturn('MyDecryptedToken');
		$this->secureRandom
			->expects($this->once())
			->method('generate')
			->with(128)
			->willReturn('NewToken');
		$token = new DefaultToken();
		$token->setUid('JohnDoe');
		$this->defaultTokenMapper
			->expects($this->once())
			->method('getTokenById')
			->with(123)
			->willReturn($token);

		$expected = new JSONResponse(
			[
				'access_token' => 'MyDecryptedToken',
				'token_type' => 'Bearer',
				'expires_in' => 3600,
				'refresh_token' => 'NewToken',
				'user_id' => 'JohnDoe',
			]
		);
		$this->assertEquals($expected, $this->oauthApiController->getToken('MySecretCode'));
	}

}
