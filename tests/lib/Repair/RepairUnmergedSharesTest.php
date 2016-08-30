<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test\Repair;


use OC\Repair\RepairUnmergedShares;
use OC\Share\Constants;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Test\TestCase;
use OC\Share20\DefaultShareProvider;
use OCP\IUserManager;
use OCP\IGroupManager;

/**
 * Tests for repairing invalid shares
 *
 * @group DB
 *
 * @see \OC\Repair\RepairUnmergedShares
 */
class RepairUnmergedSharesTest extends TestCase {

	/** @var IRepairStep */
	private $repair;

	/** @var \OCP\IDBConnection */
	private $connection;

	/** @var int */
	private $lastShareTime;

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	protected function setUp() {
		parent::setUp();

		$config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$config->expects($this->any())
			->method('getSystemValue')
			->with('version')
			->will($this->returnValue('9.0.3.0'));

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->deleteAllShares();

		$this->userManager = $this->getMock('\OCP\IUserManager');
		$this->groupManager = $this->getMock('\OCP\IGroupManager');

		// used to generate incremental stimes
		$this->lastShareTime = time();

		/** @var \OCP\IConfig $config */
		$this->repair = new RepairUnmergedShares($config, $this->connection, $this->userManager, $this->groupManager);
	}

	protected function tearDown() {
		$this->deleteAllShares();

		parent::tearDown();
	}

	protected function deleteAllShares() {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('share')->execute();
	}

	private function createShare($type, $sourceId, $recipient, $targetName, $permissions, $parentId = null) {
		$this->lastShareTime += 100;
		$qb = $this->connection->getQueryBuilder();
		$values = [
			'share_type' => $qb->expr()->literal($type),
			'share_with' => $qb->expr()->literal($recipient),
			'uid_owner' => $qb->expr()->literal('user1'),
			'item_type' => $qb->expr()->literal('folder'),
			'item_source' => $qb->expr()->literal($sourceId),
			'item_target' => $qb->expr()->literal('/' . $sourceId),
			'file_source' => $qb->expr()->literal($sourceId),
			'file_target' => $qb->expr()->literal($targetName),
			'permissions' => $qb->expr()->literal($permissions),
			'stime' => $qb->expr()->literal($this->lastShareTime),
		];
		if ($parentId !== null) {
			$values['parent'] = $qb->expr()->literal($parentId);
		}
		$qb->insert('share')
			->values($values)
			->execute();

		return $this->connection->lastInsertId('*PREFIX*share');
	}

	private function getShareById($id) {
		$query = $this->connection->getQueryBuilder();
		$results = $query
			->select('*')
			->from('share')
			->where($query->expr()->eq('id', $query->expr()->literal($id)))
			->execute()
			->fetchAll();

		if (!empty($results)) {
			return $results[0];
		}
		return null;
	}

	public function sharesDataProvider() {
		/**
		 * For all these test cases we have the following situation:
		 *
		 * - "user1" is the share owner
		 * - "user2" is the recipient, and member of "recipientgroup1" and "recipientgroup2"
		 * - "user1" is member of "samegroup1", "samegroup2" for same group tests
		 */
		return [
			[
				// #0 legitimate share:
				// - outsider shares with group1, group2
				// - recipient renamed, resulting in subshares
				// - one subshare for each group share
				// - targets of subshare all match
				[
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup1', '/test', 31],
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup2', '/test', 31],
					// child of the previous ones
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/test renamed', 31, 0],
					// child of the previous ones
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/test renamed', 31, 1],
					// different unrelated share
					[Constants::SHARE_TYPE_GROUP, 456, 'recipientgroup1', '/test (4)', 31],
				],
				[
					['/test', 31],
					['/test', 31],
					// leave them alone
					['/test renamed', 31],
					['/test renamed', 31],
					// leave unrelated alone
					['/test (4)', 31],
				]
			],
			[
				// #1 broken share:
				// - outsider shares with group1, group2
				// - only one subshare for two group shares
				[
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup1', '/test', 31],
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup2', '/test', 31],
					// child of the previous one
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/test (2)', 31, 1],
					// different unrelated share
					[Constants::SHARE_TYPE_GROUP, 456, 'recipientgroup1', '/test (4)', 31],
				],
				[
					['/test', 31],
					['/test', 31],
					['/test', 31],
					// leave unrelated alone
					['/test (4)', 31],
				]
			],
			[
				// #2 bogus share
				// - outsider shares with group1, group2
				// - one subshare for each group share, both with parenthesis
				// - but the targets do not match when grouped
				[
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup1', '/test', 31],
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup2', '/test', 31],
					// child of the previous ones
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/test (2)', 31, 0],
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/test (3)', 31, 1],
					// different unrelated share
					[Constants::SHARE_TYPE_GROUP, 456, 'recipientgroup1', '/test (4)', 31],
				],
				[
					['/test', 31],
					['/test', 31],
					// reset to original name as the sub-names have parenthesis
					['/test', 31],
					['/test', 31],
					// leave unrelated alone
					['/test (4)', 31],
				]
			],
			[
				// #3 bogus share
				// - outsider shares with group1, group2
				// - one subshare for each group share, both renamed manually
				// - but the targets do not match when grouped
				[
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup1', '/test', 31],
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup2', '/test', 31],
					// child of the previous ones
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/test_renamed (1 legit paren)', 31, 0],
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/test_renamed (2 legit paren)', 31, 1],
					// different unrelated share
					[Constants::SHARE_TYPE_GROUP, 456, 'recipientgroup1', '/test (4)', 31],
				],
				[
					['/test', 31],
					['/test', 31],
					// reset to less recent subshare name
					['/test_renamed (2 legit paren)', 31],
					['/test_renamed (2 legit paren)', 31],
					// leave unrelated alone
					['/test (4)', 31],
				]
			],
			[
				// #4 bogus share
				// - outsider shares with group1, group2
				// - one subshare for each group share, one with parenthesis
				// - but the targets do not match when grouped
				[
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup1', '/test', 31],
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup2', '/test', 31],
					// child of the previous ones
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/test (2)', 31, 0],
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/test_renamed', 31, 1],
					// different unrelated share
					[Constants::SHARE_TYPE_GROUP, 456, 'recipientgroup1', '/test (4)', 31],
				],
				[
					['/test', 31],
					['/test', 31],
					// reset to less recent subshare name but without parenthesis
					['/test_renamed', 31],
					['/test_renamed', 31],
					// leave unrelated alone
					['/test (4)', 31],
				]
			],
			[
				// #5 bogus share
				// - outsider shares with group1, group2
				// - one subshare for each group share
				// - first subshare not renamed (as in real world scenario)
				// - but the targets do not match when grouped
				[
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup1', '/test', 31],
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup2', '/test', 31],
					// child of the previous ones
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/test', 31, 0],
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/test (2)', 31, 1],
					// different unrelated share
					[Constants::SHARE_TYPE_GROUP, 456, 'recipientgroup1', '/test (4)', 31],
				],
				[
					['/test', 31],
					['/test', 31],
					// reset to original name
					['/test', 31],
					['/test', 31],
					// leave unrelated alone
					['/test (4)', 31],
				]
			],
			[
				// #6 bogus share:
				// - outsider shares with group1, group2
				// - one subshare for each group share
				// - non-matching targets
				// - recipient deletes one duplicate (unshare from self, permissions 0)
				[
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup1', '/test', 31],
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup2', '/test', 15],
					// child of the previous ones
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/test (2)', 0, 0],
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/test (3)', 15, 1],
					// different unrelated share
					[Constants::SHARE_TYPE_GROUP, 456, 'recipientgroup1', '/test (4)', 31],
				],
				[
					['/test', 31],
					['/test', 15],
					// subshares repaired and permissions restored to the max allowed
					['/test', 31],
					['/test', 15],
					// leave unrelated alone
					['/test (4)', 31],
				]
			],
			[
				// #7 bogus share:
				// - outsider shares with group1, group2
				// - one subshare for each group share
				// - non-matching targets
				// - recipient deletes ALL duplicates (unshare from self, permissions 0)
				[
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup1', '/test', 31],
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup2', '/test', 15],
					// child of the previous ones
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/test (2)', 0, 0],
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/test (3)', 0, 1],
					// different unrelated share
					[Constants::SHARE_TYPE_GROUP, 456, 'recipientgroup1', '/test (4)', 31],
				],
				[
					['/test', 31],
					['/test', 15],
					// subshares target repaired but left "deleted" as it was the user's choice
					['/test', 0],
					['/test', 0],
					// leave unrelated alone
					['/test (4)', 31],
				]
			],
			[
				// #8 bogus share:
				// - outsider shares with group1, group2 and also user2
				// - one subshare for each group share
				// - one extra share entry for direct share to user2
				// - non-matching targets
				// - user share has more permissions
				[
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup1', '/test', 1],
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup2', '/test', 15],
					// child of the previous ones
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/test (2)', 1, 0],
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/test (3)', 15, 1],
					[Constants::SHARE_TYPE_USER, 123, 'user2', '/test (4)', 31],
					// different unrelated share
					[Constants::SHARE_TYPE_GROUP, 456, 'recipientgroup1', '/test (5)', 31],
				],
				[
					['/test', 1],
					['/test', 15],
					// subshares repaired
					['/test', 1],
					['/test', 15],
					['/test', 31],
					// leave unrelated alone
					['/test (5)', 31],
				]
			],
			[
				// #9 bogus share:
				// - outsider shares with group1 and also user2
				// - no subshare at all
				// - one extra share entry for direct share to user2
				// - non-matching targets
				// - user share has more permissions
				[
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup1', '/test', 1],
					[Constants::SHARE_TYPE_USER, 123, 'user2', '/test (2)', 31],
					// different unrelated share
					[Constants::SHARE_TYPE_GROUP, 456, 'recipientgroup1', '/test (5)', 31],
				],
				[
					['/test', 1],
					// user share repaired
					['/test', 31],
					// leave unrelated alone
					['/test (5)', 31],
				]
			],
			[
				// #10 legitimate share with own group:
				// - insider shares with both groups the user is already in
				// - no subshares in this case
				[
					[Constants::SHARE_TYPE_GROUP, 123, 'samegroup1', '/test', 31],
					[Constants::SHARE_TYPE_GROUP, 123, 'samegroup2', '/test', 31],
					// different unrelated share
					[Constants::SHARE_TYPE_GROUP, 456, 'recipientgroup1', '/test (4)', 31],
				],
				[
					// leave all alone
					['/test', 31],
					['/test', 31],
					// leave unrelated alone
					['/test (4)', 31],
				]
			],
			[
				// #11 legitimate shares:
				// - group share with same group
				// - group share with other group
				// - user share where recipient renamed
				// - user share where recipient did not rename
				[
					[Constants::SHARE_TYPE_GROUP, 123, 'samegroup1', '/test', 31],
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup1', '/test', 31],
					[Constants::SHARE_TYPE_USER, 123, 'user3', '/test legit rename', 31],
					[Constants::SHARE_TYPE_USER, 123, 'user4', '/test', 31],
					// different unrelated share
					[Constants::SHARE_TYPE_GROUP, 456, 'recipientgroup1', '/test (4)', 31],
				],
				[
					// leave all alone
					['/test', 31],
					['/test', 31],
					['/test legit rename', 31],
					['/test', 31],
					// leave unrelated alone
					['/test (4)', 31],
				]
			],
			[
				// #12 legitimate share:
				// - outsider shares with group and user directly with different permissions
				// - no subshares
				// - same targets
				[
					[Constants::SHARE_TYPE_GROUP, 123, 'samegroup1', '/test', 1],
					[Constants::SHARE_TYPE_USER, 123, 'user3', '/test', 31],
					// different unrelated share
					[Constants::SHARE_TYPE_GROUP, 456, 'recipientgroup1', '/test (4)', 31],
				],
				[
					// leave all alone
					['/test', 1],
					['/test', 31],
					// leave unrelated alone
					['/test (4)', 31],
				]
			],
			[
				// #13 bogus share:
				// - outsider shares with group1, user2 and then group2
				// - user renamed share as soon as it arrived before the next share (order)
				// - one subshare for each group share
				// - one extra share entry for direct share to user2
				// - non-matching targets
				[
					// first share with group
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup1', '/test', 31],
					// recipient renames
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/first', 31, 0],
					// then direct share, user renames too
					[Constants::SHARE_TYPE_USER, 123, 'user2', '/second', 31],
					// another share with the second group
					[Constants::SHARE_TYPE_GROUP, 123, 'recipientgroup2', '/test', 31],
					// use renames it
					[DefaultShareProvider::SHARE_TYPE_USERGROUP, 123, 'user2', '/third', 31, 1],
					// different unrelated share
					[Constants::SHARE_TYPE_GROUP, 456, 'recipientgroup1', '/test (5)', 31],
				],
				[
					// group share with group1 left alone
					['/test', 31],
					// first subshare repaired
					['/third', 31],
					// direct user share repaired
					['/third', 31],
					// group share with group2 left alone
					['/test', 31],
					// second subshare repaired
					['/third', 31],
					// leave unrelated alone
					['/test (5)', 31],
				]
			],
		];
	}

	/**
	 * Test merge shares from group shares
	 *
	 * @dataProvider sharesDataProvider
	 */
	public function testMergeGroupShares($shares, $expectedShares) {
		$user1 = $this->getMock('\OCP\IUser');
		$user1->expects($this->any())
			->method('getUID')
			->will($this->returnValue('user1'));

		$user2 = $this->getMock('\OCP\IUser');
		$user2->expects($this->any())
			->method('getUID')
			->will($this->returnValue('user2'));

		$users = [$user1, $user2];

		$this->groupManager->expects($this->any())
			->method('getUserGroupIds')
			->will($this->returnValueMap([
				// owner
				[$user1, ['samegroup1', 'samegroup2']],
				// recipient
				[$user2, ['recipientgroup1', 'recipientgroup2']],
			]));

		$this->userManager->expects($this->once())
			->method('countUsers')
			->will($this->returnValue([2]));
		$this->userManager->expects($this->once())
			->method('callForAllUsers')
			->will($this->returnCallback(function(\Closure $closure) use ($users) {
				foreach ($users as $user) {
					$closure($user);
				}
			}));

		$shareIds = [];

		foreach ($shares as $share) {
			// if parent
			if (isset($share[5])) {
				// adjust to real id
				$share[5] = $shareIds[$share[5]];
			} else {
				$share[5] = null;
			}
			$shareIds[] = $this->createShare($share[0], $share[1], $share[2], $share[3], $share[4], $share[5]);
		}

		/** @var IOutput | \PHPUnit_Framework_MockObject_MockObject $outputMock */
		$outputMock = $this->getMockBuilder('\OCP\Migration\IOutput')
			->disableOriginalConstructor()
			->getMock();

		$this->repair->run($outputMock);

		foreach ($expectedShares as $index => $expectedShare) {
			$share = $this->getShareById($shareIds[$index]);
			$this->assertEquals($expectedShare[0], $share['file_target']);
			$this->assertEquals($expectedShare[1], $share['permissions']);
		}
	}

	public function duplicateNamesProvider() {
		return [
			// matching
			['filename (1).txt', true],
			['folder (2)', true],
			['filename (1)(2).txt', true],
			// non-matching
			['filename ().txt', false],
			['folder ()', false],
			['folder (1x)', false],
			['folder (x1)', false],
			['filename (a)', false],
			['filename (1).', false],
			['filename (1).txt.txt', false],
			['filename (1)..txt', false],
		];
	}

	/**
	 * @dataProvider duplicateNamesProvider
	 */
	public function testIsPotentialDuplicateName($name, $expectedResult) {
		$this->assertEquals($expectedResult, $this->invokePrivate($this->repair, 'isPotentialDuplicateName', [$name]));
	}
}

