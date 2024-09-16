<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\Settings\Personal\Security;

use OCA\Settings\Settings\Personal\Security\Password;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PasswordTest extends TestCase {

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var string */
	private $uid;

	/** @var Password */
	private $section;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->uid = 'test123';

		$this->section = new Password(
			$this->userManager,
			$this->uid
		);
	}

	public function testGetForm(): void {
		$user = $this->createMock(IUser::class);
		$this->userManager->expects($this->once())
			->method('get')
			->with($this->uid)
			->willReturn($user);
		$user->expects($this->once())
			->method('canChangePassword')
			->willReturn(true);

		$form = $this->section->getForm();

		$expected = new TemplateResponse('settings', 'settings/personal/security/password', [
			'passwordChangeSupported' => true,
		]);
		$this->assertEquals($expected, $form);
	}
}
