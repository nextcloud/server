<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Notification\IManager;
use Test\TestCase;

class SyncTest extends TestCase {

	/** @var  array */
	protected $arguments;
	/** @var  Helper|\PHPUnit_Framework_MockObject_MockObject */
	protected $helper;
	/** @var  LDAP|\PHPUnit_Framework_MockObject_MockObject */
	protected $ldapWrapper;
	/** @var  Manager|\PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;
	/** @var  UserMapping|\PHPUnit_Framework_MockObject_MockObject */
	protected $mapper;
	/** @var  Sync */
	protected $sync;
	/** @var  IConfig|\PHPUnit_Framework_MockObject_MockObject */
	protected $config;
	/** @var  IAvatarManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $avatarManager;
	/** @var  IDBConnection|\PHPUnit_Framework_MockObject_MockObject */
	protected $dbc;
	/** @var  IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $ncUserManager;
	/** @var  IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $notificationManager;
	/** @var ConnectionFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $connectionFactory;
	/** @var AccessFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $accessFactory;

	public function setUp() {
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
		$this->connectionFactory = $this->createMock(ConnectionFactory::class);
		$this->accessFactory = $this->createMock(AccessFactory::class);

		$this->arguments = [
			'helper' => $this->helper,
			'ldapWrapper' => $this->ldapWrapper,
			'userManager' => $this->userManager,
			'mapper' => $this->mapper,
			'config' => $this->config,
			'avatarManager' => $this->avatarManager,
			'dbc' => $this->dbc,
			'ncUserManager' => $this->ncUserManager,
			'notificationManager' => $this->notificationManager,
			'connectionFactory' => $this->connectionFactory,
			'accessFactory' => $this->accessFactory,
		];

		$this->sync = new Sync();
	}

	public function intervalDataProvider() {
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
	public function testUpdateInterval($userCount, $pagingSize1, $pagingSize2) {
		$this->config->expects($this->once())
			->method('setAppValue')
			->with('user_ldap', 'background_sync_interval', $this->anything())
			->willReturnCallback(function($a, $k, $interval) {
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

	public function moreResultsProvider() {
		return [
			[ 3, 3, true ],
			[ 3, 5, true ],
			[ 3, 2, false]
		];
	}

	/**
	 * @dataProvider moreResultsProvider
	 */
	public function testMoreResults($pagingSize, $results, $expected) {
		$connection = $this->createMock(Connection::class);
		$this->connectionFactory->expects($this->any())
			->method('get')
			->willReturn($connection);
		$connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($key) use ($pagingSize) {
				if($key === 'ldapPagingSize') {
					return $pagingSize;
				}
				return null;
			});

		/** @var Access|\PHPUnit_Framework_MockObject_MockObject $access */
		$access = $this->createMock(Access::class);
		$this->accessFactory->expects($this->any())
			->method('get')
			->with($connection)
			->willReturn($access);

		$access->expects($this->once())
			->method('fetchListOfUsers')
			->willReturn(array_pad([], $results, 'someUser'));
		$access->connection = $connection;
		$access->userManager = $this->userManager;

		$this->sync->setArgument($this->arguments);
		$hasMoreResults = $this->sync->runCycle(['prefix' => 's01', 'offset' => 100]);
		$this->assertSame($expected, $hasMoreResults);
	}

}
