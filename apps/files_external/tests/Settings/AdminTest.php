<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Tests\Settings;

use OCA\Files_External\Lib\Auth\Password\GlobalAuth;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Service\GlobalStoragesService;
use OCA\Files_External\Settings\Admin;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Encryption\IManager;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AdminTest extends TestCase {
	private IManager&MockObject $encryptionManager;
	private GlobalStoragesService&MockObject $globalStoragesService;
	private BackendService&MockObject $backendService;
	private GlobalAuth&MockObject $globalAuth;
	private IInitialState&MockObject $initialState;
	private IURLGenerator&MockObject $urlGenerator;
	private IAppManager&MockObject $appManager;
	private Admin $admin;

	protected function setUp(): void {
		parent::setUp();
		$this->encryptionManager = $this->createMock(IManager::class);
		$this->globalStoragesService = $this->createMock(GlobalStoragesService::class);
		$this->backendService = $this->createMock(BackendService::class);
		$this->globalAuth = $this->createMock(GlobalAuth::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->appManager = $this->createMock(IAppManager::class);

		$this->admin = new Admin(
			$this->encryptionManager,
			$this->globalStoragesService,
			$this->backendService,
			$this->globalAuth,
			$this->initialState,
			$this->urlGenerator,
			$this->appManager,
		);
	}

	public function testGetForm(): void {
		$backends = [
			$this->createMock(Backend::class),
		];
		$backends[0]->method('checkDependencies')->willReturn([]);
		$backends[0]->method('getIdentifier')->willReturn('backend1');

		$authMechanism = $this->createMock(GlobalAuth::class);
		$this->encryptionManager
			->expects($this->once())
			->method('isEnabled')
			->willReturn(false);
		$this->backendService
			->expects($this->atLeastOnce())
			->method('getAvailableBackends')
			->willReturn($backends);
		$this->backendService
			->expects($this->atLeastOnce())
			->method('getAuthMechanisms')
			->willReturn([$authMechanism]);
		$this->backendService
			->expects($this->atLeastOnce())
			->method('isUserMountingAllowed')
			->willReturn(true);
		$this->globalAuth
			->expects($this->once())
			->method('getAuth')
			->with('')
			->willReturn(['asdf' => 'asdf']);

		$initialState = [];
		$this->initialState
			->expects($this->atLeastOnce())
			->method('provideInitialState')
			->willReturnCallback(function () use (&$initialState): void {
				$args = func_get_args();
				$initialState[$args[0]] = $args[1];
			});

		$expected = new TemplateResponse('files_external', 'settings', renderAs: '');
		$this->assertEquals($expected, $this->admin->getForm());
		$this->assertEquals($initialState, [
			'settings' => [
				'docUrl' => '',
				'dependencyIssues' => [
					'messages' => [],
					'modules' => [],
				],
				'isAdmin' => true,
				'hasEncryption' => false,
			],
			'global-credentials' => [
				'uid' => '',
				'asdf' => 'asdf',
			],
			'allowedBackends' => ['backend1'],
			'backends' => $backends,
			'authMechanisms' => [$authMechanism],
			'user-mounting' => [
				'allowUserMounting' => true,
				'allowedBackends' => [],
			],
		]);
	}

	public function testGetSection(): void {
		$this->assertSame('externalstorages', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(40, $this->admin->getPriority());
	}
}
