<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\UpdateNotification\Tests\Settings;

use OCA\UpdateNotification\Settings\Admin;
use OCA\UpdateNotification\UpdateChecker;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\L10N\ILanguageIterator;
use OCP\Support\Subscription\IRegistry;
use OCP\Util;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class AdminTest extends TestCase {
	/** @var IFactory|\PHPUnit\Framework\MockObject\MockObject */
	protected $l10nFactory;
	/** @var Admin */
	private $admin;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var IAppConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $appConfig;
	/** @var UpdateChecker|\PHPUnit\Framework\MockObject\MockObject */
	private $updateChecker;
	/** @var IGroupManager|\PHPUnit\Framework\MockObject\MockObject */
	private $groupManager;
	/** @var IDateTimeFormatter|\PHPUnit\Framework\MockObject\MockObject */
	private $dateTimeFormatter;
	/** @var IRegistry|\PHPUnit\Framework\MockObject\MockObject */
	private $subscriptionRegistry;
	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;
	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $logger;
	/** IInitialState|\PHPUnit\Framework\MockObject\MockObject */
	private $initialState;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->updateChecker = $this->createMock(UpdateChecker::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->dateTimeFormatter = $this->createMock(IDateTimeFormatter::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->subscriptionRegistry = $this->createMock(IRegistry::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->initialState = $this->createMock(IInitialState::class);

		$this->admin = new Admin(
			$this->config,
			$this->appConfig,
			$this->updateChecker,
			$this->groupManager,
			$this->dateTimeFormatter,
			$this->l10nFactory,
			$this->subscriptionRegistry,
			$this->userManager,
			$this->logger,
			$this->initialState
		);
	}

	public function testGetFormWithUpdate(): void {
		$this->userManager
			->expects($this->once())
			->method('countUsersTotal')
			->willReturn(5);
		$channels = [
			'daily',
			'beta',
			'stable',
			'production',
		];
		$currentChannel = Util::getChannel();
		if ($currentChannel === 'git') {
			$channels[] = 'git';
		}
		$this->appConfig
			->expects($this->once())
			->method('getValueInt')
			->with('core', 'lastupdatedat', 0)
			->willReturn(12345);
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('updatenotification', 'notify_groups', '["admin"]')
			->willReturn('["admin"]');
		$this->config
			->method('getSystemValue')
			->willReturnMap([
				['updater.server.url', 'https://updates.nextcloud.com/updater_server/', 'https://updates.nextcloud.com/updater_server/'],
				['upgrade.disable-web', false, false],
			]);
		$this->dateTimeFormatter
			->expects($this->once())
			->method('formatDateTime')
			->with(12345)
			->willReturn('LastCheckedReturnValue');
		$this->updateChecker
			->expects($this->once())
			->method('getUpdateState')
			->willReturn([
				'updateAvailable' => true,
				'updateVersion' => '8.1.2',
				'updateVersionString' => 'Nextcloud 8.1.2',
				'downloadLink' => 'https://downloads.nextcloud.org/server',
				'changes' => [],
				'updaterEnabled' => true,
				'versionIsEol' => false,
			]);

		$group = $this->createMock(IGroup::class);
		$group->expects($this->any())
			->method('getDisplayName')
			->willReturn('Administrators');
		$group->expects($this->any())
			->method('getGID')
			->willReturn('admin');
		$this->groupManager->expects($this->once())
			->method('get')
			->with('admin')
			->willReturn($group);

		$this->subscriptionRegistry
			->expects($this->once())
			->method('delegateHasValidSubscription')
			->willReturn(true);

		$this->initialState->expects($this->once())
			->method('provideInitialState')
			->with('data', [
				'isNewVersionAvailable' => true,
				'isUpdateChecked' => true,
				'lastChecked' => 'LastCheckedReturnValue',
				'currentChannel' => Util::getChannel(),
				'channels' => $channels,
				'newVersion' => '8.1.2',
				'newVersionString' => 'Nextcloud 8.1.2',
				'downloadLink' => 'https://downloads.nextcloud.org/server',
				'changes' => [],
				'webUpdaterEnabled' => true,
				'isWebUpdaterRecommended' => true,
				'updaterEnabled' => true,
				'versionIsEol' => false,
				'isDefaultUpdateServerURL' => true,
				'updateServerURL' => 'https://updates.nextcloud.com/updater_server/',
				'notifyGroups' => [
					['id' => 'admin', 'displayname' => 'Administrators'],
				],
				'hasValidSubscription' => true,
			]);

		$expected = new TemplateResponse('updatenotification', 'admin', [], '');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetFormWithUpdateAndChangedUpdateServer(): void {
		$this->userManager
			->expects($this->once())
			->method('countUsersTotal')
			->willReturn(5);
		$channels = [
			'daily',
			'beta',
			'stable',
			'production',
		];
		$currentChannel = Util::getChannel();
		if ($currentChannel === 'git') {
			$channels[] = 'git';
		}

		$this->appConfig
			->expects($this->once())
			->method('getValueInt')
			->with('core', 'lastupdatedat', 0)
			->willReturn(12345);
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('updatenotification', 'notify_groups', '["admin"]')
			->willReturn('["admin"]');
		$this->config
			->method('getSystemValue')
			->willReturnMap([
				['updater.server.url', 'https://updates.nextcloud.com/updater_server/', 'https://updates.nextcloud.com/updater_server_changed/'],
				['upgrade.disable-web', false, true],
			]);
		$this->dateTimeFormatter
			->expects($this->once())
			->method('formatDateTime')
			->with('12345')
			->willReturn('LastCheckedReturnValue');
		$this->updateChecker
			->expects($this->once())
			->method('getUpdateState')
			->willReturn([
				'updateAvailable' => true,
				'updateVersion' => '8.1.2',
				'updateVersionString' => 'Nextcloud 8.1.2',
				'downloadLink' => 'https://downloads.nextcloud.org/server',
				'changes' => [],
				'updaterEnabled' => true,
				'versionIsEol' => false,
			]);

		$group = $this->createMock(IGroup::class);
		$group->expects($this->any())
			->method('getDisplayName')
			->willReturn('Administrators');
		$group->expects($this->any())
			->method('getGID')
			->willReturn('admin');
		$this->groupManager->expects($this->once())
			->method('get')
			->with('admin')
			->willReturn($group);

		$this->subscriptionRegistry
			->expects($this->once())
			->method('delegateHasValidSubscription')
			->willReturn(true);

		$this->initialState->expects($this->once())
			->method('provideInitialState')
			->with('data', [
				'isNewVersionAvailable' => true,
				'isUpdateChecked' => true,
				'lastChecked' => 'LastCheckedReturnValue',
				'currentChannel' => Util::getChannel(),
				'channels' => $channels,
				'newVersion' => '8.1.2',
				'newVersionString' => 'Nextcloud 8.1.2',
				'downloadLink' => 'https://downloads.nextcloud.org/server',
				'changes' => [],
				'webUpdaterEnabled' => false,
				'isWebUpdaterRecommended' => true,
				'updaterEnabled' => true,
				'versionIsEol' => false,
				'isDefaultUpdateServerURL' => false,
				'updateServerURL' => 'https://updates.nextcloud.com/updater_server_changed/',
				'notifyGroups' => [
					['id' => 'admin', 'displayname' => 'Administrators'],
				],
				'hasValidSubscription' => true,
			]);

		$expected = new TemplateResponse('updatenotification', 'admin', [], '');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetFormWithUpdateAndCustomersUpdateServer(): void {
		$this->userManager
			->expects($this->once())
			->method('countUsersTotal')
			->willReturn(5);
		$channels = [
			'daily',
			'beta',
			'stable',
			'production',
		];
		$currentChannel = Util::getChannel();
		if ($currentChannel === 'git') {
			$channels[] = 'git';
		}

		$this->appConfig
			->expects($this->once())
			->method('getValueInt')
			->with('core', 'lastupdatedat', 0)
			->willReturn(12345);
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('updatenotification', 'notify_groups', '["admin"]')
			->willReturn('["admin"]');
		$this->config
			->method('getSystemValue')
			->willReturnMap([
				['updater.server.url', 'https://updates.nextcloud.com/updater_server/', 'https://updates.nextcloud.com/customers/ABC-DEF/'],
				['upgrade.disable-web', false, false],
			]);
		$this->dateTimeFormatter
			->expects($this->once())
			->method('formatDateTime')
			->with('12345')
			->willReturn('LastCheckedReturnValue');
		$this->updateChecker
			->expects($this->once())
			->method('getUpdateState')
			->willReturn([
				'updateAvailable' => true,
				'updateVersion' => '8.1.2',
				'updateVersionString' => 'Nextcloud 8.1.2',
				'downloadLink' => 'https://downloads.nextcloud.org/server',
				'changes' => [],
				'updaterEnabled' => true,
				'versionIsEol' => false,
			]);

		$group = $this->createMock(IGroup::class);
		$group->expects($this->any())
			->method('getDisplayName')
			->willReturn('Administrators');
		$group->expects($this->any())
			->method('getGID')
			->willReturn('admin');
		$this->groupManager->expects($this->once())
			->method('get')
			->with('admin')
			->willReturn($group);

		$this->subscriptionRegistry
			->expects($this->once())
			->method('delegateHasValidSubscription')
			->willReturn(true);

		$this->initialState->expects($this->once())
			->method('provideInitialState')
			->with('data', [
				'isNewVersionAvailable' => true,
				'isUpdateChecked' => true,
				'lastChecked' => 'LastCheckedReturnValue',
				'currentChannel' => Util::getChannel(),
				'channels' => $channels,
				'newVersion' => '8.1.2',
				'newVersionString' => 'Nextcloud 8.1.2',
				'downloadLink' => 'https://downloads.nextcloud.org/server',
				'changes' => [],
				'webUpdaterEnabled' => true,
				'isWebUpdaterRecommended' => true,
				'updaterEnabled' => true,
				'versionIsEol' => false,
				'isDefaultUpdateServerURL' => true,
				'updateServerURL' => 'https://updates.nextcloud.com/customers/ABC-DEF/',
				'notifyGroups' => [
					['id' => 'admin', 'displayname' => 'Administrators'],
				],
				'hasValidSubscription' => true,
			]);

		$expected = new TemplateResponse('updatenotification', 'admin', [], '');
		$this->assertEquals($expected, $this->admin->getForm());
	}


	public function testGetSection(): void {
		$this->assertSame('overview', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(11, $this->admin->getPriority());
	}

	public function changesProvider() {
		return [
			[ #0, all info, en
				[
					'changelogURL' => 'https://go.to.changelog',
					'whatsNew' => [
						'en' => [
							'regular' => ['content'],
						],
						'de' => [
							'regular' => ['inhalt'],
						]
					],
				],
				'en',
				[
					'changelogURL' => 'https://go.to.changelog',
					'whatsNew' => [
						'regular' => ['content'],
					],
				]
			],
			[ #1, all info, de
				[
					'changelogURL' => 'https://go.to.changelog',
					'whatsNew' => [
						'en' => [
							'regular' => ['content'],
						],
						'de' => [
							'regular' => ['inhalt'],
						]
					],
				],
				'de',
				[
					'changelogURL' => 'https://go.to.changelog',
					'whatsNew' => [
						'regular' => ['inhalt'],
					]
				],
			],
			[ #2, just changelog
				[ 'changelogURL' => 'https://go.to.changelog' ],
				'en',
				[ 'changelogURL' => 'https://go.to.changelog' ],
			],
			[ #3 nothing
				[],
				'ru',
				[]
			]
		];
	}

	/**
	 * @dataProvider changesProvider
	 */
	public function testFilterChanges($changes, $userLang, $expectation): void {
		$iterator = $this->createMock(ILanguageIterator::class);
		$iterator->expects($this->any())
			->method('current')
			->willReturnOnConsecutiveCalls('es', $userLang, 'it', 'en');
		$iterator->expects($this->any())
			->method('valid')
			->willReturn(true);

		$this->l10nFactory->expects($this->atMost(1))
			->method('getLanguageIterator')
			->willReturn($iterator);
		$result = $this->invokePrivate($this->admin, 'filterChanges', [$changes]);
		$this->assertSame($expectation, $result);
	}
}
