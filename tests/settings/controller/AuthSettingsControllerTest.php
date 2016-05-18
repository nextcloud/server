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
		$result = [
			'token1',
			'token2',
		];
		$this->userManager->expects($this->once())
			->method('get')
			->with($this->uid)
			->will($this->returnValue($this->user));
		$this->tokenProvider->expects($this->once())
			->method('getTokenByUser')
			->with($this->user)
			->will($this->returnValue($result));

		$this->assertEquals($result, $this->controller->index());
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

		$this->secureRandom->expects($this->exactly(4))
			->method('generate')
			->with(5, implode('', range('A', 'Z')))
			->will($this->returnValue('XXXXX'));
		$newToken = 'XXXXX-XXXXX-XXXXX-XXXXX';

		$this->tokenProvider->expects($this->once())
			->method('generateToken')
			->with($newToken, $this->uid, $password, $name, IToken::PERMANENT_TOKEN)
			->will($this->returnValue($deviceToken));

		$expected = [
			'token' => $newToken,
			'deviceToken' => $deviceToken,
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

}
