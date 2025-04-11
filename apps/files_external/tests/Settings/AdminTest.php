<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Tests\Settings;

use OCA\Files_External\Lib\Auth\Password\GlobalAuth;
use OCA\Files_External\MountConfig;
use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Service\GlobalStoragesService;
use OCA\Files_External\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Encryption\IManager;
use Test\TestCase;

class AdminTest extends TestCase {
	/** @var Admin */
	private $admin;
	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	private $encryptionManager;
	/** @var GlobalStoragesService|\PHPUnit\Framework\MockObject\MockObject */
	private $globalStoragesService;
	/** @var BackendService|\PHPUnit\Framework\MockObject\MockObject */
	private $backendService;
	/** @var GlobalAuth|\PHPUnit\Framework\MockObject\MockObject */
	private $globalAuth;

	protected function setUp(): void {
		parent::setUp();
		$this->encryptionManager = $this->createMock(IManager::class);
		$this->globalStoragesService = $this->createMock(GlobalStoragesService::class);
		$this->backendService = $this->createMock(BackendService::class);
		$this->globalAuth = $this->createMock(GlobalAuth::class);

		$this->admin = new Admin(
			$this->encryptionManager,
			$this->globalStoragesService,
			$this->backendService,
			$this->globalAuth
		);
	}

	public function testGetForm(): void {
		$this->encryptionManager
			->expects($this->once())
			->method('isEnabled')
			->willReturn(false);
		$this->globalStoragesService
			->expects($this->once())
			->method('getStorages')
			->willReturn(['a', 'b', 'c']);
		$this->backendService
			->expects($this->once())
			->method('getAvailableBackends')
			->willReturn(['d', 'e', 'f']);
		$this->backendService
			->expects($this->once())
			->method('getAuthMechanisms')
			->willReturn(['g', 'h', 'i']);
		$this->backendService
			->expects($this->once())
			->method('isUserMountingAllowed')
			->willReturn(true);
		$this->backendService
			->expects($this->exactly(2))
			->method('getBackends')
			->willReturn([]);
		$this->globalAuth
			->expects($this->once())
			->method('getAuth')
			->with('')
			->willReturn('asdf:asdf');
		$params = [
			'encryptionEnabled' => false,
			'visibilityType' => BackendService::VISIBILITY_ADMIN,
			'storages' => ['a', 'b', 'c'],
			'backends' => ['d', 'e', 'f'],
			'authMechanisms' => ['g', 'h', 'i'],
			'dependencies' => MountConfig::dependencyMessage($this->backendService->getBackends()),
			'allowUserMounting' => true,
			'globalCredentials' => 'asdf:asdf',
			'globalCredentialsUid' => '',
		];
		$expected = new TemplateResponse('files_external', 'settings', $params, '');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('externalstorages', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(40, $this->admin->getPriority());
	}
}
