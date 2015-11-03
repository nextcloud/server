<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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


namespace OC\Tests\Share;


use Test\TestCase;

/**
 * Class HookTests
 *
 * @group DB
 *
 * @package OC\Tests\Share
 */
class HookTests extends TestCase {

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*share` WHERE `item_type` = ?');
		$query->execute(array('test'));

		parent::tearDown();
	}

	public function testPostAddToGroup() {

		/** @var \OC\DB\Connection $connection */
		$connection = \OC::$server->getDatabaseConnection();
		$query = $connection->createQueryBuilder();
		$expr = $query->expr();

		// add some dummy values to the private $updateTargets variable
		$this->invokePrivate(
			new \OC\Share\Hooks(),
			'updateTargets',
			[
				[
					'group1' =>
						[
							[
								'`item_type`' => $expr->literal('test'),
								'`item_source`' => $expr->literal('42'),
								'`item_target`' => $expr->literal('42'),
								'`file_target`' => $expr->literal('test'),
								'`share_type`' => $expr->literal('2'),
								'`share_with`' => $expr->literal('group1'),
								'`uid_owner`' => $expr->literal('owner'),
								'`permissions`' => $expr->literal('0'),
								'`stime`' => $expr->literal('676584'),
								'`file_source`' => $expr->literal('42'),
							],
							[
								'`item_type`' => $expr->literal('test'),
								'`item_source`' => $expr->literal('42'),
								'`item_target`' => $expr->literal('42 (2)'),
								'`share_type`' => $expr->literal('2'),
								'`share_with`' => $expr->literal('group1'),
								'`uid_owner`' => $expr->literal('owner'),
								'`permissions`' => $expr->literal('0'),
								'`stime`' => $expr->literal('676584'),
							]
						],
					'group2' =>
						[
							[
								'`item_type`' => $expr->literal('test'),
								'`item_source`' => $expr->literal('42'),
								'`item_target`' => $expr->literal('42'),
								'`share_type`' => $expr->literal('2'),
								'`share_with`' => $expr->literal('group2'),
								'`uid_owner`' => $expr->literal('owner'),
								'`permissions`' => $expr->literal('0'),
								'`stime`' => $expr->literal('676584'),
							]
						]
				]
			]
		);

		// add unique targets for group1 to database
		\OC\Share\Hooks::post_addToGroup(['gid' => 'group1']);


		$query->select('`share_with`')->from('`*PREFIX*share`');
		$result = $query->execute()->fetchAll();
		$this->assertSame(2, count($result));
		foreach ($result as $r) {
			$this->assertSame('group1', $r['share_with']);
		}
	}

}
