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
use OCP\IUrlGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * @group DB
 */
class ServerTest extends TestCase {
	/** @var IDBConnection */
	private $connection;
	/** @var Server&MockObject */
	private $admin;
	/** @var IInitialState&MockObject */
	private $initialStateService;
	/** @var ProfileManager&MockObject */
	private $profileManager;
	/** @var ITimeFactory&MockObject */
	private $timeFactory;
	/** @var IConfig&MockObject */
	private $config;
	/** @var IAppConfig&MockObject */
	private $appConfig;
	/** @var IL10N&MockObject */
	private $l10n;
	/** @var IUrlGenerator&MockObject */
	private $urlGenerator;

	protected function setUp(): void {
		parent::setUp();
		$this->connection = \OC::$server->getDatabaseConnection();
		$this->initialStateService = $this->createMock(IInitialState::class);
		$this->profileManager = $this->createMock(ProfileManager::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->urlGenerator = $this->createMock(IUrlGenerator::class);

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
