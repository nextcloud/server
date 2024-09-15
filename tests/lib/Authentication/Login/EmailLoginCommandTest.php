<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\EmailLoginCommand;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;

class EmailLoginCommandTest extends ALoginCommandTest {
	/** @var IUserManager|MockObject */
	private $userManager;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);

		$this->cmd = new EmailLoginCommand(
			$this->userManager
		);
	}

	public function testProcessAlreadyLoggedIn(): void {
		$data = $this->getLoggedInLoginData();

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcessNotAnEmailLogin(): void {
		$data = $this->getFailedLoginData();
		$this->userManager->expects($this->never())
			->method('getByEmail')
			->with($this->username)
			->willReturn([]);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcessDuplicateEmailLogin(): void {
		$data = $this->getFailedLoginData();
		$data->setUsername('user@example.com');
		$this->userManager->expects($this->once())
			->method('getByEmail')
			->with('user@example.com')
			->willReturn([
				$this->createMock(IUser::class),
				$this->createMock(IUser::class),
			]);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcessUidIsEmail(): void {
		$email = 'user@domain.com';
		$data = $this->getFailedLoginData();
		$data->setUsername($email);
		$emailUser = $this->createMock(IUser::class);
		$emailUser->expects($this->any())
			->method('getUID')
			->willReturn($email);
		$this->userManager->expects($this->once())
			->method('getByEmail')
			->with($email)
			->willReturn([
				$emailUser,
			]);
		$this->userManager->expects($this->never())
			->method('checkPassword');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
		$this->assertFalse($data->getUser());
		$this->assertEquals($email, $data->getUsername());
	}

	public function testProcessWrongPassword(): void {
		$email = 'user@domain.com';
		$data = $this->getFailedLoginData();
		$data->setUsername($email);
		$emailUser = $this->createMock(IUser::class);
		$emailUser->expects($this->any())
			->method('getUID')
			->willReturn('user2');
		$this->userManager->expects($this->once())
			->method('getByEmail')
			->with($email)
			->willReturn([
				$emailUser,
			]);
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with(
				'user2',
				$this->password
			)
			->willReturn(false);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
		$this->assertFalse($data->getUser());
		$this->assertEquals($email, $data->getUsername());
	}

	public function testProcess(): void {
		$email = 'user@domain.com';
		$data = $this->getFailedLoginData();
		$data->setUsername($email);
		$emailUser = $this->createMock(IUser::class);
		$emailUser->expects($this->any())
			->method('getUID')
			->willReturn('user2');
		$this->userManager->expects($this->once())
			->method('getByEmail')
			->with($email)
			->willReturn([
				$emailUser,
			]);
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with(
				'user2',
				$this->password
			)
			->willReturn($emailUser);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
		$this->assertEquals($emailUser, $data->getUser());
		$this->assertEquals('user2', $data->getUsername());
	}
}
