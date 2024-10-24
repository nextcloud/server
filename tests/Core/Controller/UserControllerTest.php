<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Core\Controller;

use OC\Core\Controller\UserController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use Test\TestCase;

class UserControllerTest extends TestCase {
	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;

	/** @var UserController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->controller = new UserController(
			'core',
			$this->createMock(IRequest::class),
			$this->userManager
		);
	}

	public function testGetDisplayNames(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getDisplayName')
			->willReturn('FooDisplay Name');

		$this->userManager
			->method('get')
			->willReturnCallback(function ($uid) use ($user) {
				if ($uid === 'foo') {
					return $user;
				}
				return null;
			});

		$expected = new JSONResponse([
			'users' => [
				'foo' => 'FooDisplay Name',
				'bar' => 'bar',
			],
			'status' => 'success'
		]);

		$result = $this->controller->getDisplayNames(['foo', 'bar']);
		$this->assertEquals($expected, $result);
	}
}
