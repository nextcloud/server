<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests\Jobs;

use OCA\User_LDAP\Access;
use OCA\User_LDAP\AccessFactory;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\ConnectionFactory;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\Jobs\Sync;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\Manager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Notification\IManager;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * @group DB
 */
class SyncTest extends TestCase {
	protected Helper&MockObject $helper;
	protected LDAP&MockObject $ldapWrapper;
	protected Manager&MockObject $userManager;
	protected UserMapping&MockObject $mapper;
	protected IConfig&MockObject $config;
	protected IAvatarManager&MockObject $avatarManager;
	protected IDBConnection&MockObject $dbc;
	protected IUserManager&MockObject $ncUserManager;
	protected IManager&MockObject $notificationManager;
	protected ConnectionFactory&MockObject $connectionFactory;
	protected AccessFactory&MockObject $accessFactory;
	protected array $arguments = [];
	protected Sync $sync;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = $this->createMock(Helper::class);
		$this->ldapWrapper = $this->createMock(LDAP::class);
		$this->userManager = $this->createMock(Manager::class);
		$this->mapper = $this->createMock(UserMapping::class);
		$this->config = $this->createMock(IConfig::class);
		$this->avatarManager = $this->createMock(IAvatarManager::class);
		$this->dbc = $this->createMock(IDBConnection::class);
		$this->ncUserManager = $this->createMock(IUserManager::class);
		$this->notificationManager = $this->createMock(IManager::class);
		$this->connectionFactory = $this->getMockBuilder(ConnectionFactory::class)
			->setConstructorArgs([
				$this->ldapWrapper,
			])
			->getMock();
		$this->accessFactory = $this->createMock(AccessFactory::class);

		$this->sync = new Sync(
			Server::get(ITimeFactory::class),
			Server::get(IEventDispatcher::class),
			$this->config,
			$this->dbc,
			$this->avatarManager,
			$this->ncUserManager,
			Server::get(LoggerInterface::class),
			$this->notificationManager,
			$this->mapper,
			$this->helper,
			$this->connectionFactory,
			$this->accessFactory,
		);

		$this->sync->overwritePropertiesForTest($this->ldapWrapper);
	}

	public static function intervalDataProvider(): array {
		return [
			[
				0, 1000, 750
			],
			[
				22,	0, 50
			],
			[
				500, 500, 500
			],
			[
				1357, 0, 0
			],
			[
				421337, 2000, 3000
			]
		];
	}

	/**
	 * @dataProvider intervalDataProvider
	 */
	public function testUpdateInterval(int $userCount, int $pagingSize1, int $pagingSize2): void {
		$this->config->expects($this->once())
			->method('setAppValue')
			->with('user_ldap', 'background_sync_interval', $this->anything())
			->willReturnCallback(function ($a, $k, $interval) {
				$this->assertTrue($interval >= SYNC::MIN_INTERVAL);
				$this->assertTrue($interval <= SYNC::MAX_INTERVAL);
				return true;
			});
		$this->config->expects($this->atLeastOnce())
			->method('getAppKeys')
			->willReturn([
				'blabla',
				'ldap_paging_size',
				's07blabla',
				'installed',
				's07ldap_paging_size'
			]);
		$this->config->expects($this->exactly(2))
			->method('getAppValue')
			->willReturnOnConsecutiveCalls($pagingSize1, $pagingSize2);

		$this->mapper->expects($this->atLeastOnce())
			->method('count')
			->willReturn($userCount);

		$this->sync->setArgument($this->arguments);
		$this->sync->updateInterval();
	}

	public static function moreResultsProvider(): array {
		return [
			[ 3, 3, true ],
			[ 3, 5, true ],
			[ 3, 2, false],
			[ 0, 4, false],
			[ null, 4, false]
		];
	}

	/**
	 * @dataProvider moreResultsProvider
	 */
	public function testMoreResults($pagingSize, $results, $expected): void {
		$connection = $this->getMockBuilder(Connection::class)
			->setConstructorArgs([
				$this->ldapWrapper,
			])
			->getMock();
		$this->connectionFactory->expects($this->any())
			->method('get')
			->willReturn($connection);
		$connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($key) use ($pagingSize) {
				if ($key === 'ldapPagingSize') {
					return $pagingSize;
				}
				return null;
			});

		/** @var Access&MockObject $access */
		$access = $this->createMock(Access::class);
		$this->accessFactory->expects($this->any())
			->method('get')
			->with($connection)
			->willReturn($access);

		$this->userManager->expects($this->any())
			->method('getAttributes')
			->willReturn(['dn', 'uid', 'mail', 'displayname']);

		$access->expects($this->once())
			->method('fetchListOfUsers')
			->willReturn(array_pad([], $results, 'someUser'));
		$access->expects($this->any())
			->method('combineFilterWithAnd')
			->willReturn('pseudo=filter');
		$access->connection = $connection;
		$access->userManager = $this->userManager;

		$this->sync->setArgument($this->arguments);
		$hasMoreResults = $this->sync->runCycle(['prefix' => 's01', 'offset' => 100]);
		$this->assertSame($expected, $hasMoreResults);
	}

	public static function cycleDataProvider(): array {
		$lastCycle = ['prefix' => 's01', 'offset' => 1000];
		$lastCycle2 = ['prefix' => '', 'offset' => 1000];
		return [
			[ null, ['s01'], ['prefix' => 's01', 'offset' => 0] ],
			[ null, [''], ['prefix' => '', 'offset' => 0] ],
			[ $lastCycle, ['s01', 's02'], ['prefix' => 's02', 'offset' => 0] ],
			[ $lastCycle, [''], ['prefix' => '', 'offset' => 0] ],
			[ $lastCycle2, ['', 's01'], ['prefix' => 's01', 'offset' => 0] ],
			[ $lastCycle, [], null ],
		];
	}

	/**
	 * @dataProvider cycleDataProvider
	 */
	public function testDetermineNextCycle(?array $cycleData, array $prefixes, ?array $expectedCycle): void {
		$this->helper->expects($this->any())
			->method('getServerConfigurationPrefixes')
			->with(true)
			->willReturn($prefixes);

		if (is_array($expectedCycle)) {
			$calls = [
				['user_ldap', 'background_sync_prefix', $expectedCycle['prefix']],
				['user_ldap', 'background_sync_offset', $expectedCycle['offset']],
			];
			$this->config->expects($this->exactly(2))
				->method('setAppValue')
				->willReturnCallback(function () use (&$calls) {
					$expected = array_shift($calls);
					$this->assertEquals($expected, func_get_args());
				});
		} else {
			$this->config->expects($this->never())
				->method('setAppValue');
		}

		$this->sync->setArgument($this->arguments);
		$nextCycle = $this->sync->determineNextCycle($cycleData);

		if ($expectedCycle === null) {
			$this->assertNull($nextCycle);
		} else {
			$this->assertSame($expectedCycle['prefix'], $nextCycle['prefix']);
			$this->assertSame($expectedCycle['offset'], $nextCycle['offset']);
		}
	}

	public function testQualifiesToRun(): void {
		$cycleData = ['prefix' => 's01'];

		$this->config->expects($this->exactly(2))
			->method('getAppValue')
			->willReturnOnConsecutiveCalls(time() - 60 * 40, time() - 60 * 20);

		$this->sync->setArgument($this->arguments);
		$this->assertTrue($this->sync->qualifiesToRun($cycleData));
		$this->assertFalse($this->sync->qualifiesToRun($cycleData));
	}

	public static function runDataProvider(): array {
		return [
			#0 - one LDAP server, reset
			[[
				'prefixes' => [''],
				'scheduledCycle' => ['prefix' => '', 'offset' => '4500'],
				'pagingSize' => 500,
				'usersThisCycle' => 0,
				'expectedNextCycle' => ['prefix' => '', 'offset' => '0'],
				'mappedUsers' => 123,
			]],
			#1 - 2 LDAP servers, next prefix
			[[
				'prefixes' => ['', 's01'],
				'scheduledCycle' => ['prefix' => '', 'offset' => '4500'],
				'pagingSize' => 500,
				'usersThisCycle' => 0,
				'expectedNextCycle' => ['prefix' => 's01', 'offset' => '0'],
				'mappedUsers' => 123,
			]],
			#2 - 2 LDAP servers, rotate prefix
			[[
				'prefixes' => ['', 's01'],
				'scheduledCycle' => ['prefix' => 's01', 'offset' => '4500'],
				'pagingSize' => 500,
				'usersThisCycle' => 0,
				'expectedNextCycle' => ['prefix' => '', 'offset' => '0'],
				'mappedUsers' => 123,
			]],
		];
	}

	/**
	 * @dataProvider runDataProvider
	 */
	public function testRun(array $runData): void {
		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(function ($app, $key, $default) use ($runData) {
				if ($app === 'core' && $key === 'backgroundjobs_mode') {
					return 'cron';
				}
				if ($app = 'user_ldap') {
					// for getCycle()
					if ($key === 'background_sync_prefix') {
						return $runData['scheduledCycle']['prefix'];
					}
					if ($key === 'background_sync_offset') {
						return $runData['scheduledCycle']['offset'];
					}
					// for qualifiesToRun()
					if ($key === $runData['scheduledCycle']['prefix'] . '_lastChange') {
						return time() - 60 * 40;
					}
					// for getMinPagingSize
					if ($key === $runData['scheduledCycle']['prefix'] . 'ldap_paging_size') {
						return $runData['pagingSize'];
					}
				}

				return $default;
			});

		$calls = [
			['user_ldap', 'background_sync_prefix', $runData['expectedNextCycle']['prefix']],
			['user_ldap', 'background_sync_offset', $runData['expectedNextCycle']['offset']],
			['user_ldap', 'background_sync_interval', '43200'],
		];
		$this->config->expects($this->exactly(3))
			->method('setAppValue')
			->willReturnCallback(function () use (&$calls) {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});
		$this->config->expects($this->any())
			->method('getAppKeys')
			->with('user_ldap')
			->willReturn([$runData['scheduledCycle']['prefix'] . 'ldap_paging_size']);

		$this->helper->expects($this->any())
			->method('getServerConfigurationPrefixes')
			->with(true)
			->willReturn($runData['prefixes']);

		$connection = $this->getMockBuilder(Connection::class)
			->setConstructorArgs([
				$this->ldapWrapper,
			])
			->getMock();
		$this->connectionFactory->expects($this->any())
			->method('get')
			->willReturn($connection);
		$connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($key) use ($runData) {
				if ($key === 'ldapPagingSize') {
					return $runData['pagingSize'];
				}
				return null;
			});

		/** @var Access&MockObject $access */
		$access = $this->createMock(Access::class);
		$this->accessFactory->expects($this->any())
			->method('get')
			->with($connection)
			->willReturn($access);

		$this->userManager->expects($this->any())
			->method('getAttributes')
			->willReturn(['dn', 'uid', 'mail', 'displayname']);

		$access->expects($this->once())
			->method('fetchListOfUsers')
			->willReturn(array_pad([], $runData['usersThisCycle'], 'someUser'));
		$access->expects($this->any())
			->method('combineFilterWithAnd')
			->willReturn('pseudo=filter');
		$access->connection = $connection;
		$access->userManager = $this->userManager;

		$this->mapper->expects($this->any())
			->method('count')
			->willReturn($runData['mappedUsers']);

		$this->sync->run($this->arguments);
	}
}
