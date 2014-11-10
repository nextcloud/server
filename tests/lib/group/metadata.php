<?php

/**
 * Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Group;

class Test_MetaData extends \Test\TestCase {
	private function getGroupManagerMock() {
		return $this->getMockBuilder('\OC\Group\Manager')
			->disableOriginalConstructor()
			->getMock();
	}

	private function getGroupMock() {
		$group = $this->getMockBuilder('\OC\Group\Group')
			->disableOriginalConstructor()
			->getMock();

		$group->expects($this->exactly(9))
			->method('getGID')
			->will($this->onConsecutiveCalls(
				'admin', 'admin', 'admin',
				'g2', 'g2', 'g2',
				'g3', 'g3', 'g3'));

		$group->expects($this->exactly(3))
			->method('count')
			->with('')
			->will($this->onConsecutiveCalls(2, 3, 5));

		return $group;
	}


	public function testGet() {
		$groupManager = $this->getGroupManagerMock();
		$groupMetaData = new \OC\Group\MetaData('foo', true, $groupManager);
		$group = $this->getGroupMock();
		$groups = array_fill(0, 3, $group);

		$groupManager->expects($this->once())
			->method('search')
			->with('')
			->will($this->returnValue($groups));

		list($adminGroups, $ordinaryGroups) = $groupMetaData->get();

		$this->assertSame(1, count($adminGroups));
		$this->assertSame(2, count($ordinaryGroups));

		$this->assertSame('g2', $ordinaryGroups[0]['name']);
		$this->assertSame(3, $ordinaryGroups[0]['usercount']);
	}

	public function testGetWithSorting() {
		$groupManager = $this->getGroupManagerMock();
		$groupMetaData = new \OC\Group\MetaData('foo', true, $groupManager);
		$groupMetaData->setSorting($groupMetaData::SORT_USERCOUNT);
		$group = $this->getGroupMock();
		$groups = array_fill(0, 3, $group);

		$groupManager->expects($this->once())
			->method('search')
			->with('')
			->will($this->returnValue($groups));

		list($adminGroups, $ordinaryGroups) = $groupMetaData->get();

		$this->assertSame(1, count($adminGroups));
		$this->assertSame(2, count($ordinaryGroups));

		$this->assertSame('g3', $ordinaryGroups[0]['name']);
		$this->assertSame(5, $ordinaryGroups[0]['usercount']);
	}

	public function testGetWithCache() {
		$groupManager = $this->getGroupManagerMock();
		$groupMetaData = new \OC\Group\MetaData('foo', true, $groupManager);
		$group = $this->getGroupMock();
		$groups = array_fill(0, 3, $group);

		$groupManager->expects($this->once())
			->method('search')
			->with('')
			->will($this->returnValue($groups));

		//two calls, if caching fails call counts for group and groupmanager
		//are exceeded
		$groupMetaData->get();
		$groupMetaData->get();
	}

	//get() does not need to be tested with search parameters, because they are
	//solely and only passed to GroupManager and Group.

}
