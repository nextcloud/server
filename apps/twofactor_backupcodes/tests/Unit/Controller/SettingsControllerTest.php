<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Tests\Unit\Controller;

use OCA\TwoFactorBackupCodes\Controller\SettingsController;
use OCA\TwoFactorBackupCodes\Service\BackupCodeStorage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SettingsControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private BackupCodeStorage&MockObject $storage;
	private IUserSession&MockObject $userSession;
	private SettingsController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->storage = $this->createMock(BackupCodeStorage::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->controller = new SettingsController('twofactor_backupcodes', $this->request, $this->storage, $this->userSession);
	}

	public function testCreateCodes(): void {
		$user = $this->createMock(IUser::class);

		$codes = ['a', 'b'];
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->storage->expects($this->once())
			->method('createCodes')
			->with($user)
			->willReturn($codes);
		$this->storage->expects($this->once())
			->method('getBackupCodesState')
			->with($user)
			->willReturn(['state']);

		$expected = [
			'codes' => $codes,
			'state' => ['state'],
		];
		$response = $this->controller->createCodes();
		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals($expected, $response->getData());
	}
}
