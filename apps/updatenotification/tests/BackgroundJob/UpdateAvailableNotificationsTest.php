<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\UpdateNotification\Tests\BackgroundJob;

use OC\Installer;
use OC\Updater\VersionCheck;
use OCA\UpdateNotification\BackgroundJob\UpdateAvailableNotifications;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use OCP\ServerVersion;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UpdateAvailableNotificationsTest extends TestCase {
	private ServerVersion $serverVersion;
	private IConfig|MockObject $config;
	private IManager|MockObject $notificationManager;
	private IGroupManager|MockObject $groupManager;
	private IAppManager|MockObject $appManager;
	private IAppConfig|MockObject $appConfig;
	private ITimeFactory|MockObject $timeFactory;
	private Installer|MockObject $installer;
	private VersionCheck|MockObject $versionCheck;

	protected function setUp(): void {
		parent::setUp();

		$this->serverVersion = $this->createMock(ServerVersion::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->notificationManager = $this->createMock(IManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->installer = $this->createMock(Installer::class);
		$this->versionCheck = $this->createMock(VersionCheck::class);
	}

	/**
	 * @param array $methods
	 * @return UpdateAvailableNotifications|MockObject
	 */
	protected function getJob(array $methods = []) {
		if (empty($methods)) {
			return new UpdateAvailableNotifications(
				$this->timeFactory,
				$this->serverVersion,
				$this->config,
				$this->appConfig,
				$this->notificationManager,
				$this->groupManager,
				$this->appManager,
				$this->installer,
				$this->versionCheck,
			);
		}
		{
			return $this->getMockBuilder(UpdateAvailableNotifications::class)
				->setConstructorArgs([
					$this->timeFactory,
					$this->serverVersion,
					$this->config,
					$this->appConfig,
					$this->notificationManager,
					$this->groupManager,
					$this->appManager,
					$this->installer,
					$this->versionCheck,
				])
				->onlyMethods($methods)
				->getMock();
		}
	}

	public function testRun(): void {
		$job = $this->getJob([
			'checkCoreUpdate',
			'checkAppUpdates',
		]);

		$job->expects($this->once())
			->method('checkCoreUpdate');
		$job->expects($this->once())
			->method('checkAppUpdates');

		$this->config->expects($this->exactly(2))
			->method('getSystemValueBool')
			->willReturnMap([
				['has_internet_connection', true, true],
				['debug', false, true],
			]);

		self::invokePrivate($job, 'run', [null]);
	}

	public function testRunNoInternet(): void {
		$job = $this->getJob([
			'checkCoreUpdate',
			'checkAppUpdates',
		]);

		$job->expects($this->never())
			->method('checkCoreUpdate');
		$job->expects($this->never())
			->method('checkAppUpdates');

		$this->config->method('getSystemValueBool')
			->with('has_internet_connection', true)
			->willReturn(false);

		self::invokePrivate($job, 'run', [null]);
	}

	public function dataCheckCoreUpdate(): array {
		return [
			['daily', null, null, null, null],
			['git', null, null, null, null],
			['beta', [], null, null, null],
			['beta', false, false, null, null],
			['beta', false, false, null, 13],
			['beta', [
				'version' => '9.2.0',
				'versionstring' => 'Nextcloud 11.0.0',
			], '9.2.0', 'Nextcloud 11.0.0', null],
			['stable', [], null, null, null],
			['stable', false, false, null, null],
			['stable', false, false, null, 6],
			['stable', [
				'version' => '9.2.0',
				'versionstring' => 'Nextcloud 11.0.0',
			], '9.2.0', 'Nextcloud 11.0.0', null],
			['production', [], null, null, null],
			['production', false, false, null, null],
			['production', false, false, null, 2],
			['production', [
				'version' => '9.2.0',
				'versionstring' => 'Nextcloud 11.0.0',
			], '9.2.0', 'Nextcloud 11.0.0', null],
		];
	}

	/**
	 * @dataProvider dataCheckCoreUpdate
	 *
	 * @param string $channel
	 * @param mixed $versionCheck
	 * @param null|string $version
	 * @param null|string $readableVersion
	 * @param null|int $errorDays
	 */
	public function testCheckCoreUpdate(string $channel, $versionCheck, $version, $readableVersion, $errorDays): void {
		$job = $this->getJob([
			'createNotifications',
			'clearErrorNotifications',
			'sendErrorNotifications',
		]);

		$this->serverVersion->expects($this->once())
			->method('getChannel')
			->willReturn($channel);

		if ($versionCheck === null) {
			$this->versionCheck->expects($this->never())
				->method('check');
		} else {
			$this->versionCheck->expects($this->once())
				->method('check')
				->willReturn($versionCheck);
		}

		if ($version === null) {
			$job->expects($this->never())
				->method('createNotifications');
			$job->expects($versionCheck === null ? $this->never() : $this->once())
				->method('clearErrorNotifications');
		} elseif ($version === false) {
			$job->expects($this->never())
				->method('createNotifications');
			$job->expects($this->never())
				->method('clearErrorNotifications');

			$this->appConfig->expects($this->once())
				->method('getAppValueInt')
				->willReturn($errorDays ?? 0);
			$this->appConfig->expects($this->once())
				->method('setAppValueInt')
				->with('update_check_errors', $errorDays + 1);
			$job->expects($errorDays !== null ? $this->once() : $this->never())
				->method('sendErrorNotifications')
				->with($errorDays + 1);
		} else {
			$this->appConfig->expects($this->once())
				->method('setAppValueInt')
				->with('update_check_errors', 0);
			$job->expects($this->once())
				->method('clearErrorNotifications');
			$job->expects($this->once())
				->method('createNotifications')
				->with('core', $version, $readableVersion);
		}

		self::invokePrivate($job, 'checkCoreUpdate');
	}

	public function dataCheckAppUpdates(): array {
		return [
			[
				['app1', 'app2'],
				[
					['app1', false],
					['app2', '1.9.2'],
				],
				[
					['app2', '1.9.2', ''],
				],
			],
		];
	}

	/**
	 * @dataProvider dataCheckAppUpdates
	 *
	 * @param string[] $apps
	 * @param array $isUpdateAvailable
	 * @param array $notifications
	 */
	public function testCheckAppUpdates(array $apps, array $isUpdateAvailable, array $notifications): void {
		$job = $this->getJob([
			'isUpdateAvailable',
			'createNotifications',
		]);

		$this->appManager->expects($this->once())
			->method('getInstalledApps')
			->willReturn($apps);

		$job->expects($this->exactly(\count($apps)))
			->method('isUpdateAvailable')
			->willReturnMap($isUpdateAvailable);

		$i = 0;
		$job->expects($this->exactly(\count($notifications)))
			->method('createNotifications')
			->willReturnCallback(function () use ($notifications, &$i): void {
				$this->assertEquals($notifications[$i], func_get_args());
				$i++;
			});


		self::invokePrivate($job, 'checkAppUpdates');
	}

	public function dataCreateNotifications(): array {
		return [
			['app1', '1.0.0', '1.0.0', false, false, null, null],
			['app2', '1.0.1', '1.0.0', '1.0.0', true, ['user1'], [['user1']]],
			['app3', '1.0.1', false, false, true, ['user2', 'user3'], [['user2'], ['user3']]],
		];
	}

	/**
	 * @dataProvider dataCreateNotifications
	 *
	 * @param string $app
	 * @param string $version
	 * @param string|false $lastNotification
	 * @param string|false $callDelete
	 * @param bool $createNotification
	 * @param string[]|null $users
	 * @param array|null $userNotifications
	 */
	public function testCreateNotifications(string $app, string $version, $lastNotification, $callDelete, $createNotification, $users, $userNotifications): void {
		$job = $this->getJob([
			'deleteOutdatedNotifications',
			'getUsersToNotify',
		]);

		$this->appConfig->expects($this->once())
			->method('getAppValueString')
			->with($app, '')
			->willReturn($lastNotification ?: '');

		if ($lastNotification !== $version) {
			$this->appConfig->expects($this->once())
				->method('setAppValueString')
				->with($app, $version);
		}

		if ($callDelete === false) {
			$job->expects($this->never())
				->method('deleteOutdatedNotifications');
		} else {
			$job->expects($this->once())
				->method('deleteOutdatedNotifications')
				->with($app, $callDelete);
		}

		if ($users === null) {
			$job->expects($this->never())
				->method('getUsersToNotify');
		} else {
			$job->expects($this->once())
				->method('getUsersToNotify')
				->willReturn($users);
		}

		if ($createNotification) {
			$notification = $this->createMock(INotification::class);
			$notification->expects($this->once())
				->method('setApp')
				->with('updatenotification')
				->willReturnSelf();
			$notification->expects($this->once())
				->method('setDateTime')
				->willReturnSelf();
			$notification->expects($this->once())
				->method('setObject')
				->with($app, $version)
				->willReturnSelf();
			$notification->expects($this->once())
				->method('setSubject')
				->with('update_available')
				->willReturnSelf();

			if ($userNotifications !== null) {
				$notification->expects($this->exactly(\count($userNotifications)))
					->method('setUser')
					->willReturnSelf();

				$this->notificationManager->expects($this->exactly(\count($userNotifications)))
					->method('notify');
			}

			$this->notificationManager->expects($this->once())
				->method('createNotification')
				->willReturn($notification);
		} else {
			$this->notificationManager->expects($this->never())
				->method('createNotification');
		}

		self::invokePrivate($job, 'createNotifications', [$app, $version]);
	}

	public function dataGetUsersToNotify(): array {
		return [
			[['g1', 'g2'], ['g1' => null, 'g2' => ['u1', 'u2']], ['u1', 'u2']],
			[['g3', 'g4'], ['g3' => ['u1', 'u2'], 'g4' => ['u2', 'u3']], ['u1', 'u2', 'u3']],
		];
	}

	/**
	 * @dataProvider dataGetUsersToNotify
	 * @param string[] $groups
	 * @param array $groupUsers
	 * @param string[] $expected
	 */
	public function testGetUsersToNotify(array $groups, array $groupUsers, array $expected): void {
		$job = $this->getJob();

		$this->appConfig->expects($this->once())
			->method('getAppValueArray')
			->with('notify_groups', ['admin'])
			->willReturn($groups);

		$groupMap = [];
		foreach ($groupUsers as $gid => $uids) {
			if ($uids === null) {
				$group = null;
			} else {
				$group = $this->getGroup($gid);
				$group->expects($this->any())
					->method('getUsers')
					->willReturn($this->getUsers($uids));
			}
			$groupMap[] = [$gid, $group];
		}
		$this->groupManager->expects($this->exactly(\count($groups)))
			->method('get')
			->willReturnMap($groupMap);

		$result = self::invokePrivate($job, 'getUsersToNotify');
		$this->assertEquals($expected, $result);

		// Test caching
		$result = self::invokePrivate($job, 'getUsersToNotify');
		$this->assertEquals($expected, $result);
	}

	public function dataDeleteOutdatedNotifications(): array {
		return [
			['app1', '1.1.0'],
			['app2', '1.2.0'],
		];
	}

	/**
	 * @dataProvider dataDeleteOutdatedNotifications
	 * @param string $app
	 * @param string $version
	 */
	public function testDeleteOutdatedNotifications(string $app, string $version): void {
		$notification = $this->createMock(INotification::class);
		$notification->expects($this->once())
			->method('setApp')
			->with('updatenotification')
			->willReturnSelf();
		$notification->expects($this->once())
			->method('setObject')
			->with($app, $version)
			->willReturnSelf();

		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->willReturn($notification);
		$this->notificationManager->expects($this->once())
			->method('markProcessed')
			->with($notification);

		$job = $this->getJob();
		self::invokePrivate($job, 'deleteOutdatedNotifications', [$app, $version]);
	}

	/**
	 * @param string[] $userIds
	 * @return IUser[]|MockObject[]
	 */
	protected function getUsers(array $userIds): array {
		$users = [];
		foreach ($userIds as $uid) {
			$user = $this->createMock(IUser::class);
			$user->expects($this->any())
				->method('getUID')
				->willReturn($uid);
			$users[] = $user;
		}
		return $users;
	}

	/**
	 * @param string $gid
	 * @return IGroup|MockObject
	 */
	protected function getGroup(string $gid) {
		$group = $this->createMock(IGroup::class);
		$group->expects($this->any())
			->method('getGID')
			->willReturn($gid);
		return $group;
	}
}
