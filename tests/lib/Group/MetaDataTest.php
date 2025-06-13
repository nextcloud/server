<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Group;

use OC\Group\MetaData;
use OCP\IUserSession;

class MetaDataTest extends \Test\TestCase {
	private \OC\Group\Manager $groupManager;
	private IUserSession $userSession;
	private MetaData $groupMetadata;
	private bool $isAdmin = true;
	private bool $isDelegatedAdmin = true;

	protected function setUp(): void {
		parent::setUp();
		$this->groupManager = $this->getMockBuilder('\OC\Group\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->createMock(IUserSession::class);
		$this->groupMetadata = new MetaData(
			'foo',
			$this->isAdmin,
			$this->isDelegatedAdmin,
			$this->groupManager,
			$this->userSession
		);
	}

	private function getGroupMock($countCallCount = 0) {
		$group = $this->getMockBuilder('\OC\Group\Group')
			->disableOriginalConstructor()
			->getMock();

		$group->expects($this->exactly(6))
			->method('getGID')->willReturnOnConsecutiveCalls('admin', 'admin', 'g2', 'g2', 'g3', 'g3');

		$group->expects($this->exactly(3))
			->method('getDisplayName')->willReturnOnConsecutiveCalls('admin', 'g2', 'g3');

		$group->expects($this->exactly($countCallCount))
			->method('count')
			->with('')->willReturnOnConsecutiveCalls(2, 3, 5);

		return $group;
	}


	public function testGet(): void {
		$group = $this->getGroupMock();
		$groups = array_fill(0, 3, $group);

		$this->groupManager->expects($this->once())
			->method('search')
			->with('')
			->willReturn($groups);

		[$adminGroups, $ordinaryGroups] = $this->groupMetadata->get();

		$this->assertSame(1, count($adminGroups));
		$this->assertSame(2, count($ordinaryGroups));

		$this->assertSame('g2', $ordinaryGroups[0]['name']);
		// user count is not loaded
		$this->assertSame(0, $ordinaryGroups[0]['usercount']);
	}

	public function testGetWithSorting(): void {
		$this->groupMetadata->setSorting(1);
		$group = $this->getGroupMock(3);
		$groups = array_fill(0, 3, $group);

		$this->groupManager->expects($this->once())
			->method('search')
			->with('')
			->willReturn($groups);

		[$adminGroups, $ordinaryGroups] = $this->groupMetadata->get();

		$this->assertSame(1, count($adminGroups));
		$this->assertSame(2, count($ordinaryGroups));

		$this->assertSame('g3', $ordinaryGroups[0]['name']);
		$this->assertSame(5, $ordinaryGroups[0]['usercount']);
	}

	public function testGetWithCache(): void {
		$group = $this->getGroupMock();
		$groups = array_fill(0, 3, $group);

		$this->groupManager->expects($this->once())
			->method('search')
			->with('')
			->willReturn($groups);

		//two calls, if caching fails call counts for group and groupmanager
		//are exceeded
		$this->groupMetadata->get();
		$this->groupMetadata->get();
	}

	//get() does not need to be tested with search parameters, because they are
	//solely and only passed to GroupManager and Group.

	public function testGetGroupsAsAdmin(): void {
		$this->groupManager
			->expects($this->once())
			->method('search')
			->with('Foo')
			->willReturn(['DummyValue']);

		$expected = ['DummyValue'];
		$this->assertSame($expected, $this->invokePrivate($this->groupMetadata, 'getGroups', ['Foo']));
	}
}
