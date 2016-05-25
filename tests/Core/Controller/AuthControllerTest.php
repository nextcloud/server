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

namespace Tests\Core\Controller;

use OC\AppFramework\Http;
use OC\Authentication\Exceptions\ClientLoginPendingException;
use OC\Authentication\Exceptions\InvalidAccessTokenException;
use OC\Core\Controller\AuthController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use Test\TestCase;

class AuthControllerTest extends TestCase {

	private $appName;
	private $request;
	private $coordinator;
	private $urlGenerator;
	private $userSession;

	/** @var AuthController */
	private $controller;

	protected function setUp() {
		parent::setUp();

		$this->appName = 'core';
		$this->request = $this->getMock('\OCP\IRequest');
		$this->coordinator = $this->getMock('\OC\Authentication\ClientLogin\IClientLoginCoordinator');
		$this->urlGenerator = $this->getMock('\OCP\IURLGenerator');
		$this->userSession = $this->getMock('\OCP\IUserSession');

		$this->controller = new AuthController($this->appName, $this->request, $this->coordinator, $this->urlGenerator, $this->userSession);
	}

	public function testStart() {
		$name = 'my client';
		$token = 'tokenxyz';
		$this->coordinator->expects($this->once())
			->method('startClientLogin')
			->with($name)
			->will($this->returnValue('tokenxyz'));
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('core.auth.check', [
				'accesstoken' => $token,
			])
			->will($this->returnValue('token/url'));
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with('token/url')
			->will($this->returnValue('absolute/token/url'));
		$expected = [
			'url' => 'absolute/token/url',
			'accessToken' => $token,
		];

		$this->assertEquals($expected, $this->controller->start($name));
	}

	public function testCheck() {
		$token = 'abcdefg';
		$user = $this->getMock('\OCP\IUser');

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->coordinator->expects($this->once())
			->method('finishClientLogin')
			->with($token, $user);
		$expected = new TemplateResponse('core', 'authsuccess', [], 'guest');

		$this->assertEquals($expected, $this->controller->check($token));
	}

	public function testCheckInvalidToken() {
		$token = 'abcdefg';
		$user = $this->getMock('\OCP\IUser');

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->coordinator->expects($this->once())
			->method('finishClientLogin')
			->with($token, $user)
			->will($this->throwException(new InvalidAccessTokenException()));
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index')
			->will($this->returnValue('files/url'));
		$expected = new RedirectResponse('files/url');

		$this->assertEquals($expected, $this->controller->check($token));
	}

	public function testStatus() {
		$accessToken = 'accesstoken';
		$token = 'anewclienttoken';

		$this->coordinator->expects($this->once())
			->method('getClientToken')
			->with($accessToken)
			->will($this->returnValue($token));
		$expected = [
			'status' => 1,
			'token' => $token,
		];

		$this->assertEquals($expected, $this->controller->status($accessToken));
	}

	public function testStatusInvalidToken() {
		$accessToken = 'accesstoken';

		$this->coordinator->expects($this->once())
			->method('getClientToken')
			->with($accessToken)
			->will($this->throwException(new InvalidAccessTokenException()));
		$expected = new JSONResponse();
		$expected->setStatus(Http::STATUS_BAD_REQUEST);

		$this->assertEquals($expected, $this->controller->status($accessToken));
	}

	public function testStatusClientLoginPending() {
		$accessToken = 'accesstoken';
		$token = 'anewclienttoken';

		$this->coordinator->expects($this->once())
			->method('getClientToken')
			->with($accessToken)
			->will($this->throwException(new ClientLoginPendingException()));
		$expected = [
			'status' => 0,
			'token' => null,
		];

		$this->assertEquals($expected, $this->controller->status($accessToken));
	}

}
