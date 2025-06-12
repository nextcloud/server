<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Share;

use OC\Share\Share;
use OC\SystemConfig;
use OCP\Constants;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;

/**
 * Class Test_Share
 *
 * @group DB
 */
class ShareTest extends \Test\TestCase {
	protected $itemType;

	protected IUser $user1;
	protected IUser $user2;
	protected IUser $user3;
	protected IUser $user4;
	protected IUser $user5;
	protected IUser $user6;
	protected IUser $groupAndUser_user;

	protected IGroup $group1;
	protected IGroup $group2;
	protected IGroup $groupAndUser_group;

	protected string $resharing;
	protected string $dateInFuture;
	protected string $dateInPast;

	protected IGroupManager $groupManager;
	protected IUserManager $userManager;
	private IDBConnection $connection;

	protected function setUp(): void {
		parent::setUp();

		$this->groupManager = Server::get(IGroupManager::class);
		$this->userManager = Server::get(IUserManager::class);

		$this->userManager->clearBackends();
		$this->userManager->registerBackend(new \Test\Util\User\Dummy());

		$this->user1 = $this->userManager->createUser($this->getUniqueID('user1_'), 'pass');
		$this->user2 = $this->userManager->createUser($this->getUniqueID('user2_'), 'pass');
		$this->user3 = $this->userManager->createUser($this->getUniqueID('user3_'), 'pass');
		$this->user4 = $this->userManager->createUser($this->getUniqueID('user4_'), 'pass');
		$this->user5 = $this->userManager->createUser($this->getUniqueID('user5_'), 'pass');
		$this->user6 = $this->userManager->createUser($this->getUniqueID('user6_'), 'pass');
		$groupAndUserId = $this->getUniqueID('groupAndUser_');
		$this->groupAndUser_user = $this->userManager->createUser($groupAndUserId, 'pass');
		\OC_User::setUserId($this->user1->getUID());

		$this->groupManager->clearBackends();
		$this->groupManager->addBackend(new \Test\Util\Group\Dummy());
		$this->group1 = $this->groupManager->createGroup($this->getUniqueID('group1_'));
		$this->group2 = $this->groupManager->createGroup($this->getUniqueID('group2_'));
		$this->groupAndUser_group = $this->groupManager->createGroup($groupAndUserId);
		$this->connection = Server::get(IDBConnection::class);

		$this->group1->addUser($this->user1);
		$this->group1->addUser($this->user2);
		$this->group1->addUser($this->user3);
		$this->group2->addUser($this->user2);
		$this->group2->addUser($this->user4);
		$this->groupAndUser_group->addUser($this->user2);
		$this->groupAndUser_group->addUser($this->user3);

		Share::registerBackend('test', 'Test\Share\Backend');
		\OC_Hook::clear('OCP\\Share');
		\OC::registerShareHooks(Server::get(SystemConfig::class));
		$this->resharing = Server::get(IConfig::class)->getAppValue('core', 'shareapi_allow_resharing', 'yes');
		Server::get(IConfig::class)->setAppValue('core', 'shareapi_allow_resharing', 'yes');

		// 20 Minutes in the past, 20 minutes in the future.
		$now = time();
		$dateFormat = 'Y-m-d H:i:s';
		$this->dateInPast = date($dateFormat, $now - 20 * 60);
		$this->dateInFuture = date($dateFormat, $now + 20 * 60);
	}

	protected function tearDown(): void {
		$query = $this->connection->getQueryBuilder();
		$query->delete('share')->andWhere($query->expr()->eq('item_type', $query->createNamedParameter('test')));
		$query->executeStatement();
		Server::get(IConfig::class)->setAppValue('core', 'shareapi_allow_resharing', $this->resharing);

		$this->user1->delete();
		$this->user2->delete();
		$this->user3->delete();
		$this->user4->delete();
		$this->user5->delete();
		$this->user6->delete();
		$this->groupAndUser_user->delete();

		$this->group1->delete();
		$this->group2->delete();
		$this->groupAndUser_group->delete();

		$this->logout();
		parent::tearDown();
	}

	public function verifyResult($result, $expected) {
		foreach ($result as $r) {
			if (in_array($r['item_target'], $expected)) {
				$key = array_search($r['item_target'], $expected);
				unset($expected[$key]);
			}
		}
		$this->assertEmpty($expected, 'did not found all expected values');
	}

	/**
	 * @dataProvider urls
	 * @param string $url
	 * @param string $expectedResult
	 */
	public function testRemoveProtocolFromUrl($url, $expectedResult): void {
		$share = new Share();
		$result = self::invokePrivate($share, 'removeProtocolFromUrl', [$url]);
		$this->assertSame($expectedResult, $result);
	}

	public static function urls(): array {
		return [
			['http://owncloud.org', 'owncloud.org'],
			['https://owncloud.org', 'owncloud.org'],
			['owncloud.org', 'owncloud.org'],
		];
	}

	/**
	 * @dataProvider dataProviderTestGroupItems
	 * @param array $ungrouped
	 * @param array $grouped
	 */
	public function testGroupItems($ungrouped, $grouped): void {
		$result = DummyShareClass::groupItemsTest($ungrouped);

		$this->compareArrays($grouped, $result);
	}

	public function compareArrays($result, $expectedResult) {
		foreach ($expectedResult as $key => $value) {
			if (is_array($value)) {
				$this->compareArrays($result[$key], $value);
			} else {
				$this->assertSame($value, $result[$key]);
			}
		}
	}

	public static function dataProviderTestGroupItems(): array {
		return [
			// one array with one share
			[
				[ // input
					['item_source' => 1, 'permissions' => Constants::PERMISSION_ALL, 'item_target' => 't1']],
				[ // expected result
					['item_source' => 1, 'permissions' => Constants::PERMISSION_ALL, 'item_target' => 't1']]],
			// two shares both point to the same source
			[
				[ // input
					['item_source' => 1, 'permissions' => Constants::PERMISSION_READ, 'item_target' => 't1'],
					['item_source' => 1, 'permissions' => Constants::PERMISSION_UPDATE, 'item_target' => 't1'],
				],
				[ // expected result
					['item_source' => 1, 'permissions' => Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE, 'item_target' => 't1',
						'grouped' => [
							['item_source' => 1, 'permissions' => Constants::PERMISSION_READ, 'item_target' => 't1'],
							['item_source' => 1, 'permissions' => Constants::PERMISSION_UPDATE, 'item_target' => 't1'],
						]
					],
				]
			],
			// two shares both point to the same source but with different targets
			[
				[ // input
					['item_source' => 1, 'permissions' => Constants::PERMISSION_READ, 'item_target' => 't1'],
					['item_source' => 1, 'permissions' => Constants::PERMISSION_UPDATE, 'item_target' => 't2'],
				],
				[ // expected result
					['item_source' => 1, 'permissions' => Constants::PERMISSION_READ, 'item_target' => 't1'],
					['item_source' => 1, 'permissions' => Constants::PERMISSION_UPDATE, 'item_target' => 't2'],
				]
			],
			// three shares two point to the same source
			[
				[ // input
					['item_source' => 1, 'permissions' => Constants::PERMISSION_READ, 'item_target' => 't1'],
					['item_source' => 2, 'permissions' => Constants::PERMISSION_CREATE, 'item_target' => 't2'],
					['item_source' => 1, 'permissions' => Constants::PERMISSION_UPDATE, 'item_target' => 't1'],
				],
				[ // expected result
					['item_source' => 1, 'permissions' => Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE, 'item_target' => 't1',
						'grouped' => [
							['item_source' => 1, 'permissions' => Constants::PERMISSION_READ, 'item_target' => 't1'],
							['item_source' => 1, 'permissions' => Constants::PERMISSION_UPDATE, 'item_target' => 't1'],
						]
					],
					['item_source' => 2, 'permissions' => Constants::PERMISSION_CREATE, 'item_target' => 't2'],
				]
			],
		];
	}
}

class DummyShareClass extends Share {
	public static function groupItemsTest($items) {
		return parent::groupItems($items, 'test');
	}
}

class DummyHookListener {
	public static $shareType = null;

	public static function listen($params) {
		self::$shareType = $params['shareType'];
	}
}
