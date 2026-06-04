<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Encryption\Tests\Settings;

use OCA\Encryption\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ISession;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class AdminTest extends TestCase {

	protected Admin $admin;

	protected IL10N&MockObject $l;
	protected LoggerInterface&MockObject $logger;
	protected IUserSession&MockObject $userSession;
	protected IConfig&MockObject $config;
	protected IUserManager&MockObject $userManager;
	protected ISession&MockObject $session;
	protected IInitialState&MockObject $initialState;
	protected IAppConfig&MockObject $appConfig;

	protected function setUp(): void {
		parent::setUp();

		$this->l = $this->createMock(IL10N::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->config = $this->createMock(IConfig::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->session = $this->createMock(ISession::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->admin = new Admin(
			$this->l,
			$this->logger,
			$this->userSession,
			$this->config,
			$this->userManager,
			$this->session,
			$this->initialState,
			$this->appConfig,
		);
	}

	public function testGetForm(): void {
		$this->appConfig
			->method('getValueBool')
			->willReturnMap([
				['encryption', 'recoveryAdminEnabled', true]
			]);
		$this->config
			->method('getAppValue')
			->willReturnCallback(function ($app, $key, $default) {
				if ($app === 'encryption' && $key === 'recoveryAdminEnabled' && $default === '0') {
					return '1';
				}
				if ($app === 'encryption' && $key === 'encryptHomeStorage' && $default === '1') {
					return '1';
				}
				return $default;
			});

		$this->initialState
			->expects(self::once())
			->method('provideInitialState')
			->with('adminSettings', [
				'recoveryEnabled' => true,
				'initStatus' => '0',
				'encryptHomeStorage' => true,
				'masterKeyEnabled' => true
			]);
		$expected = new TemplateResponse('encryption', 'settings', renderAs: '');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('security', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(11, $this->admin->getPriority());
	}
}
