<?php

/**
 * @author Christoph Wurst <christoph@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace Test\Settings\Controller;

use OC\AppFramework\Http;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\DefaultToken;
use OC\Authentication\Token\IToken;
use OC\Settings\Controller\AuthSettingsController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Session\Exceptions\SessionNotAvailableException;
use Test\TestCase;

class AuthSettingsControllerTest extends TestCase {

	/** @var AuthSettingsController */
	private $controller;
	private $request;
	private $tokenProvider;
	private $userManager;
	private $session;
	private $secureRandom;
	private $uid;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->getMock('\OCP\IRequest');
		$this->tokenProvider = $this->getMock('\OC\Authentication\Token\IProvider');
		$this->userManager = $this->getMock('\OCP\IUserManager');
		$this->session = $this->getMock('\OCP\ISession');
		$this->secureRandom = $this->getMock('\OCP\Security\ISecureRandom');
		$this->uid = 'jane';
		$this->user = $this->getMock('\OCP\IUser');

		$this->controller = new AuthSettingsController('core', $this->request, $this->tokenProvider, $this->userManager, $this->session, $this->secureRandom, $this->uid);
	}

	public function testIndex() {
		$token1 = new DefaultToken();
		$token1->setId(100);
		$token2 = new DefaultToken();
		$token2->setId(200);
		$tokens = [
			$token1,
			$token2,
		];
		$sessionToken = new DefaultToken();
		$sessionToken->setId(100);

		$this->userManager->expects($this->once())
			->method('get')
			->with($this->uid)
			->will($this->returnValue($this->user));
		$this->tokenProvider->expects($this->once())
			->method('getTokenByUser')
			->with($this->user)
			->will($this->returnValue($tokens));
		$this->session->expects($this->once())
			->method('getId')
			->will($this->returnValue('session123'));
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('session123')
			->will($this->returnValue($sessionToken));

		$this->assertEquals([
			[
				'id' => 100,
				'name' => null,
				'lastActivity' => null,
				'type' => null,
				'canDelete' => false,
				'current' => true,
			],
			[
				'id' => 200,
				'name' => null,
				'lastActivity' => null,
				'type' => null,
				'canDelete' => true,
			]
		], $this->controller->index());
	}

	public function testCreate() {
		$name = 'Nexus 4';
		$sessionToken = $this->getMock('\OC\Authentication\Token\IToken');
		$deviceToken = $this->getMock('\OC\Authentication\Token\IToken');
		$password = '123456';

		$this->session->expects($this->once())
			->method('getId')
			->will($this->returnValue('sessionid'));
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sessionid')
			->will($this->returnValue($sessionToken));
		$this->tokenProvider->expects($this->once())
			->method('getPassword')
			->with($sessionToken, 'sessionid')
			->will($this->returnValue($password));
		$sessionToken->expects($this->once())
			->method('getLoginName')
			->will($this->returnValue('User13'));

		$this->secureRandom->expects($this->exactly(4))
			->method('generate')
			->with(5, implode('', range('A', 'Z')))
			->will($this->returnValue('XXXXX'));
		$newToken = 'XXXXX-XXXXX-XXXXX-XXXXX';

		$this->tokenProvider->expects($this->once())
			->method('generateToken')
			->with($newToken, $this->uid, 'User13', $password, $name, IToken::PERMANENT_TOKEN)
			->will($this->returnValue($deviceToken));

		$expected = [
			'token' => $newToken,
			'deviceToken' => $deviceToken,
			'loginName' => 'User13',
		];
		$this->assertEquals($expected, $this->controller->create($name));
	}

	public function testCreateSessionNotAvailable() {
		$name = 'personal phone';

		$this->session->expects($this->once())
			->method('getId')
			->will($this->throwException(new SessionNotAvailableException()));

		$expected = new JSONResponse();
		$expected->setStatus(Http::STATUS_SERVICE_UNAVAILABLE);

		$this->assertEquals($expected, $this->controller->create($name));
	}

	public function testCreateInvalidToken() {
		$name = 'Company IPhone';

		$this->session->expects($this->once())
			->method('getId')
			->will($this->returnValue('sessionid'));
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sessionid')
			->will($this->throwException(new InvalidTokenException()));

		$expected = new JSONResponse();
		$expected->setStatus(Http::STATUS_SERVICE_UNAVAILABLE);

		$this->assertEquals($expected, $this->controller->create($name));
	}

	public function testDestroy() {
		$id = 123;
		$user = $this->getMock('\OCP\IUser');

		$this->userManager->expects($this->once())
			->method('get')
			->with($this->uid)
			->will($this->returnValue($user));
		$this->tokenProvider->expects($this->once())
			->method('invalidateTokenById')
			->with($user, $id);

		$this->assertEquals([], $this->controller->destroy($id));
	}

}
