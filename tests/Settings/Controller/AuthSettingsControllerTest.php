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
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OC\Settings\Controller\AuthSettingsController;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\AppFramework\Http\JSONResponse;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\Security\ISecureRandom;
use OCP\Session\Exceptions\SessionNotAvailableException;
use Test\TestCase;

class AuthSettingsControllerTest extends TestCase {

	/** @var AuthSettingsController */
	private $controller;
	private $request;
	/** @var IProvider|\PHPUnit_Framework_MockObject_MockObject */
	private $tokenProvider;
	private $session;
	private $secureRandom;
	private $uid;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->tokenProvider = $this->createMock(IProvider::class);
		$this->session = $this->createMock(ISession::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
		$this->uid = 'jane';

		$activityManager = $this->createMock(IManager::class);
		$activityManager->method('generateEvent')
			->willReturn($this->createMock(IEvent::class));
		$logger = $this->createMock(ILogger::class);

		$this->controller = new AuthSettingsController('core', $this->request, $this->tokenProvider, $this->session, $this->secureRandom, $this->uid, $activityManager, $logger);
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

		$this->tokenProvider->expects($this->once())
			->method('getTokenByUser')
			->with($this->uid)
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
				'lastActivity' => 0,
				'type' => 0,
				'canDelete' => false,
				'current' => true,
				'scope' => ['filesystem' => true]
			],
			[
				'id' => 200,
				'name' => null,
				'lastActivity' => 0,
				'type' => 0,
				'canDelete' => true,
				'scope' => ['filesystem' => true]
			]
		], $this->controller->index());
	}

	public function testCreate() {
		$name = 'Nexus 4';
		$sessionToken = $this->createMock(IToken::class);
		$deviceToken = $this->createMock(IToken::class);
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

		$this->secureRandom->expects($this->exactly(5))
			->method('generate')
			->with(5, ISecureRandom::CHAR_HUMAN_READABLE)
			->will($this->returnValue('XXXXX'));
		$newToken = 'XXXXX-XXXXX-XXXXX-XXXXX-XXXXX';

		$this->tokenProvider->expects($this->once())
			->method('generateToken')
			->with($newToken, $this->uid, 'User13', $password, $name, IToken::PERMANENT_TOKEN)
			->will($this->returnValue($deviceToken));

		$deviceToken->expects($this->once())
			->method('jsonSerialize')
			->will($this->returnValue(['dummy' => 'dummy', 'canDelete' => true]));

		$expected = [
			'token' => $newToken,
			'deviceToken' => ['dummy' => 'dummy', 'canDelete' => true],
			'loginName' => 'User13',
		];
		$response = $this->controller->create($name);
		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals($expected, $response->getData());
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
		$user = $this->createMock(IUser::class);

		$this->tokenProvider->expects($this->once())
			->method('invalidateTokenById')
			->with($this->uid, $id);

		$this->assertEquals([], $this->controller->destroy($id));
	}

	public function testUpdateToken() {
		$token = $this->createMock(DefaultToken::class);

		$this->tokenProvider->expects($this->once())
			->method('getTokenById')
			->with($this->equalTo(42))
			->willReturn($token);

		$token->expects($this->once())
			->method('getUID')
			->willReturn('jane');

		$token->expects($this->once())
			->method('setScope')
			->with($this->equalTo([
				'filesystem' => true
			]));

		$this->tokenProvider->expects($this->once())
			->method('updateToken')
			->with($this->equalTo($token));

		$this->assertSame([], $this->controller->update(42, ['filesystem' => true]));
	}

	public function testUpdateTokenWrongUser() {
		$token = $this->createMock(DefaultToken::class);

		$this->tokenProvider->expects($this->once())
			->method('getTokenById')
			->with($this->equalTo(42))
			->willReturn($token);

		$token->expects($this->once())
			->method('getUID')
			->willReturn('foobar');

		$token->expects($this->never())
			->method('setScope');
		$this->tokenProvider->expects($this->never())
			->method('updateToken');

		$response = $this->controller->update(42, ['filesystem' => true]);
		$this->assertSame([], $response->getData());
		$this->assertSame(\OCP\AppFramework\Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	public function testUpdateTokenNonExisting() {
		$this->tokenProvider->expects($this->once())
			->method('getTokenById')
			->with($this->equalTo(42))
			->willThrowException(new InvalidTokenException('Token does not exist'));

		$this->tokenProvider->expects($this->never())
			->method('updateToken');

		$response = $this->controller->update(42, ['filesystem' => true]);
		$this->assertSame([], $response->getData());
		$this->assertSame(\OCP\AppFramework\Http::STATUS_NOT_FOUND, $response->getStatus());
	}

}
