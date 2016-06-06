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
use OC\Authentication\Token\IToken;
use OC\Core\Controller\TokenController;
use OCP\AppFramework\Http\JSONResponse;
use Test\TestCase;

class TokenControllerTest extends TestCase {

	/** \OC\Core\Controller\TokenController */
	private $tokenController;
	private $request;
	private $userManager;
	private $tokenProvider;
	private $twoFactorAuthManager;
	private $secureRandom;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->getMock('\OCP\IRequest');
		$this->userManager = $this->getMockBuilder('\OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->tokenProvider = $this->getMock('\OC\Authentication\Token\IProvider');
		$this->twoFactorAuthManager = $this->getMockBuilder('\OC\Authentication\TwoFactorAuth\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->secureRandom = $this->getMock('\OCP\Security\ISecureRandom');

		$this->tokenController = new TokenController('core', $this->request, $this->userManager, $this->tokenProvider, $this->twoFactorAuthManager, $this->secureRandom);
	}

	public function testWithoutCredentials() {
		$expected = new JSONResponse();
		$expected->setStatus(Http::STATUS_UNPROCESSABLE_ENTITY);

		$actual = $this->tokenController->generateToken(null, null);

		$this->assertEquals($expected, $actual);
	}

	public function testWithInvalidCredentials() {
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with('john', 'passme')
			->will($this->returnValue(false));
		$expected = new JSONResponse();
		$expected->setStatus(Http::STATUS_UNAUTHORIZED);

		$actual = $this->tokenController->generateToken('john', 'passme');

		$this->assertEquals($expected, $actual);
	}

	public function testWithValidCredentials() {
		$user = $this->getMock('\OCP\IUser');
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with('john', '123456')
			->will($this->returnValue($user));
		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('john'));
		$this->twoFactorAuthManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->with($user)
			->will($this->returnValue(false));
		$this->secureRandom->expects($this->once())
			->method('generate')
			->with(128)
			->will($this->returnValue('verysecurerandomtoken'));
		$this->tokenProvider->expects($this->once())
			->method('generateToken')
			->with('verysecurerandomtoken', 'john', 'john', '123456', 'unknown client', IToken::PERMANENT_TOKEN);
		$expected = [
			'token' => 'verysecurerandomtoken'
		];

		$actual = $this->tokenController->generateToken('john', '123456');

		$this->assertEquals($expected, $actual);
	}

	public function testWithValidCredentialsBut2faEnabled() {
		$user = $this->getMock('\OCP\IUser');
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with('john', '123456')
			->will($this->returnValue($user));
		$this->twoFactorAuthManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->with($user)
			->will($this->returnValue(true));
		$this->secureRandom->expects($this->never())
			->method('generate');
		$expected = new JSONResponse();
		$expected->setStatus(Http::STATUS_UNAUTHORIZED);

		$actual = $this->tokenController->generateToken('john', '123456');

		$this->assertEquals($expected, $actual);
	}

}
