<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Encryption\Tests\Settings;

use OCA\Encryption\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
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

	protected function setUp(): void {
		parent::setUp();

		$this->l = $this->getMockBuilder(IL10N::class)->getMock();
		$this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
		$this->userSession = $this->getMockBuilder(IUserSession::class)->getMock();
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->userManager = $this->getMockBuilder(IUserManager::class)->getMock();
		$this->session = $this->getMockBuilder(ISession::class)->getMock();

		$this->admin = new Admin(
			$this->l,
			$this->logger,
			$this->userSession,
			$this->config,
			$this->userManager,
			$this->session
		);
	}

	public function testGetForm(): void {
		$this->config
			->method('getAppValue')
			->will($this->returnCallback(function ($app, $key, $default) {
				if ($app === 'encryption' && $key === 'recoveryAdminEnabled' && $default === '0') {
					return '1';
				}
				if ($app === 'encryption' && $key === 'encryptHomeStorage' && $default === '1') {
					return '1';
				}
				return $default;
			}));
		$params = [
			'recoveryEnabled' => '1',
			'initStatus' => '0',
			'encryptHomeStorage' => true,
			'masterKeyEnabled' => true
		];
		$expected = new TemplateResponse('encryption', 'settings-admin', $params, '');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('security', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(11, $this->admin->getPriority());
	}
}
