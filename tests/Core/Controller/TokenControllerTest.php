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
use OC\Core\Controller\TokenController;
use OCP\AppFramework\Http\Response;
use Test\TestCase;

class TokenControllerTest extends TestCase {

	/** \OC\Core\Controller\TokenController */
	private $tokenController;
	private $request;
	private $userManager;
	private $tokenProvider;
	private $secureRandom;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->getMock('\OCP\IRequest');
		$this->userManager = $this->getMockBuilder('\OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->tokenProvider = $this->getMockBuilder('\OC\Authentication\Token\DefaultTokenProvider')
			->disableOriginalConstructor()
			->getMock();
		$this->secureRandom = $this->getMock('\OCP\Security\ISecureRandom');

		$this->tokenController = new TokenController('core', $this->request, $this->userManager, $this->tokenProvider,
			$this->secureRandom);
	}

	public function testWithoutCredentials() {
		$expected = new Response();
		$expected->setStatus(Http::STATUS_UNPROCESSABLE_ENTITY);

		$actual = $this->tokenController->generateToken(null, null);

		$this->assertEquals($expected, $actual);
	}

	public function testWithInvalidCredentials() {
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with('john', 'passme')
			->will($this->returnValue(false));
		$expected = new Response();
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
		$this->secureRandom->expects($this->once())
			->method('generate')
			->with(128)
			->will($this->returnValue('verysecurerandomtoken'));
		$this->tokenProvider->expects($this->once())
			->method('generateToken')
			->with('verysecurerandomtoken', 'john', 'john', '123456', 'unknown client', \OC\Authentication\Token\IToken::PERMANENT_TOKEN);
		$expected = [
			'token' => 'verysecurerandomtoken'
		];

		$actual = $this->tokenController->generateToken('john', '123456');

		$this->assertEquals($expected, $actual);
	}

}
