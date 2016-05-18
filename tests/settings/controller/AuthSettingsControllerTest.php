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

use OC\Settings\Controller\AuthSettingsController;
use Test\TestCase;

class AuthSettingsControllerTest extends TestCase {

	/** @var AuthSettingsController */
	private $controller;
	private $request;
	private $tokenProvider;
	private $userManager;
	private $uid;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->getMock('\OCP\IRequest');
		$this->tokenProvider = $this->getMock('\OC\Authentication\Token\IProvider');
		$this->userManager = $this->getMock('\OCP\IUserManager');
		$this->uid = 'jane';
		$this->user = $this->getMock('\OCP\IUser');

		$this->controller = new AuthSettingsController('core', $this->request, $this->tokenProvider, $this->userManager, $this->uid);
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

}
