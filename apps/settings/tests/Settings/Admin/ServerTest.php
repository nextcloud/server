<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\Settings\Admin;

use OC\Profile\ProfileManager;
use OCA\Settings\Settings\Admin\Server;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class ServerTest extends TestCase {
	private IDBConnection $connection;
	private Server&MockObject $admin;
	private IInitialState&MockObject $initialStateService;
	private ProfileManager&MockObject $profileManager;
	private ITimeFactory&MockObject $timeFactory;
	private IConfig&MockObject $config;
	private IAppConfig&MockObject $appConfig;
	private IL10N&MockObject $l10n;
	private IURLGenerator&MockObject $urlGenerator;

	protected function setUp(): void {
		parent::setUp();
		$this->connection = \OCP\Server::get(IDBConnection::class);
		$this->initialStateService = $this->createMock(IInitialState::class);
		$this->profileManager = $this->createMock(ProfileManager::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->admin = $this->getMockBuilder(Server::class)
			->onlyMethods(['cronMaxAge'])
			->setConstructorArgs([
				$this->connection,
				$this->initialStateService,
				$this->profileManager,
				$this->timeFactory,
				$this->urlGenerator,
				$this->config,
				$this->appConfig,
				$this->l10n,
			])
			->getMock();
	}

	public function testGetForm(): void {
		$this->admin->expects($this->once())
			->method('cronMaxAge')
			->willReturn(1337);
		$this->config
			->expects($this->any())
			->method('getAppValue')
			->willReturnMap([
				['core', 'lastcron', '0', '0'],
				['core', 'cronErrors', ''],
			]);
		$this->appConfig
			->expects($this->any())
			->method('getValueString')
			->willReturnCallback(fn ($a, $b, $default) => $default);
		$this->appConfig
			->expects($this->any())
			->method('getValueBool')
			->willReturnCallback(fn ($a, $b, $default) => $default);
		$this->profileManager
			->expects($this->exactly(2))
			->method('isProfileEnabled')
			->willReturn(true);
		$expected = new TemplateResponse(
			'settings',
			'settings/admin/server',
			[
				'profileEnabledGlobally' => true,
			],
			''
		);

		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('server', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(0, $this->admin->getPriority());
	}
}
