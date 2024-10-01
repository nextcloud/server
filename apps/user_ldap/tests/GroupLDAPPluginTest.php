<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\GroupPluginManager;
use OCP\GroupInterface;

class GroupLDAPPluginTest extends \Test\TestCase {

	/**
	 * @return GroupPluginManager
	 */
	private function getGroupPluginManager() {
		return new GroupPluginManager();
	}

	public function testImplementsActions(): void {
		$pluginManager = $this->getGroupPluginManager();

		$plugin = $this->getMockBuilder('OCA\User_LDAP\Tests\LDAPGroupPluginDummy')
			->setMethods(['respondToActions'])
			->getMock();

		$plugin->expects($this->any())
			->method('respondToActions')
			->willReturn(GroupInterface::CREATE_GROUP);

		$plugin2 = $this->getMockBuilder('OCA\User_LDAP\Tests\LDAPGroupPluginDummy')
			->setMethods(['respondToActions'])
			->getMock();

		$plugin2->expects($this->any())
			->method('respondToActions')
			->willReturn(GroupInterface::ADD_TO_GROUP);

		$pluginManager->register($plugin);
		$pluginManager->register($plugin2);

		$this->assertEquals($pluginManager->getImplementedActions(), GroupInterface::CREATE_GROUP | GroupInterface::ADD_TO_GROUP);
		$this->assertTrue($pluginManager->implementsActions(GroupInterface::CREATE_GROUP));
		$this->assertTrue($pluginManager->implementsActions(GroupInterface::ADD_TO_GROUP));
	}

	public function testCreateGroup(): void {
		$pluginManager = $this->getGroupPluginManager();

		$plugin = $this->getMockBuilder('OCA\User_LDAP\Tests\LDAPGroupPluginDummy')
			->setMethods(['respondToActions', 'createGroup'])
			->getMock();

		$plugin->expects($this->any())
			->method('respondToActions')
			->willReturn(GroupInterface::CREATE_GROUP);

		$plugin->expects($this->once())
			->method('createGroup')
			->with(
				$this->equalTo('group')
			);

		$pluginManager->register($plugin);
		$pluginManager->createGroup('group');
	}


	public function testCreateGroupNotRegistered(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('No plugin implements createGroup in this LDAP Backend.');

		$pluginManager = $this->getGroupPluginManager();
		$pluginManager->createGroup('foo');
	}

	public function testDeleteGroup(): void {
		$pluginManager = $this->getGroupPluginManager();

		$plugin = $this->getMockBuilder('OCA\User_LDAP\Tests\LDAPGroupPluginDummy')
			->setMethods(['respondToActions', 'deleteGroup'])
			->getMock();

		$plugin->expects($this->any())
			->method('respondToActions')
			->willReturn(GroupInterface::DELETE_GROUP);

		$plugin->expects($this->once())
			->method('deleteGroup')
			->with(
				$this->equalTo('group')
			)->willReturn(true);

		$pluginManager->register($plugin);
		$this->assertTrue($pluginManager->deleteGroup('group'));
	}


	public function testDeleteGroupNotRegistered(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('No plugin implements deleteGroup in this LDAP Backend.');

		$pluginManager = $this->getGroupPluginManager();
		$pluginManager->deleteGroup('foo');
	}

	public function testAddToGroup(): void {
		$pluginManager = $this->getGroupPluginManager();

		$plugin = $this->getMockBuilder('OCA\User_LDAP\Tests\LDAPGroupPluginDummy')
			->setMethods(['respondToActions', 'addToGroup'])
			->getMock();

		$plugin->expects($this->any())
			->method('respondToActions')
			->willReturn(GroupInterface::ADD_TO_GROUP);

		$plugin->expects($this->once())
			->method('addToGroup')
			->with(
				$this->equalTo('uid'),
				$this->equalTo('gid')
			);

		$pluginManager->register($plugin);
		$pluginManager->addToGroup('uid', 'gid');
	}


	public function testAddToGroupNotRegistered(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('No plugin implements addToGroup in this LDAP Backend.');

		$pluginManager = $this->getGroupPluginManager();
		$pluginManager->addToGroup('foo', 'bar');
	}

	public function testRemoveFromGroup(): void {
		$pluginManager = $this->getGroupPluginManager();

		$plugin = $this->getMockBuilder('OCA\User_LDAP\Tests\LDAPGroupPluginDummy')
			->setMethods(['respondToActions', 'removeFromGroup'])
			->getMock();

		$plugin->expects($this->any())
			->method('respondToActions')
			->willReturn(GroupInterface::REMOVE_FROM_GROUP);

		$plugin->expects($this->once())
			->method('removeFromGroup')
			->with(
				$this->equalTo('uid'),
				$this->equalTo('gid')
			);

		$pluginManager->register($plugin);
		$pluginManager->removeFromGroup('uid', 'gid');
	}


	public function testRemoveFromGroupNotRegistered(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('No plugin implements removeFromGroup in this LDAP Backend.');

		$pluginManager = $this->getGroupPluginManager();
		$pluginManager->removeFromGroup('foo', 'bar');
	}

	public function testCountUsersInGroup(): void {
		$pluginManager = $this->getGroupPluginManager();

		$plugin = $this->getMockBuilder('OCA\User_LDAP\Tests\LDAPGroupPluginDummy')
			->setMethods(['respondToActions', 'countUsersInGroup'])
			->getMock();

		$plugin->expects($this->any())
			->method('respondToActions')
			->willReturn(GroupInterface::COUNT_USERS);

		$plugin->expects($this->once())
			->method('countUsersInGroup')
			->with(
				$this->equalTo('gid'),
				$this->equalTo('search')
			);

		$pluginManager->register($plugin);
		$pluginManager->countUsersInGroup('gid', 'search');
	}


	public function testCountUsersInGroupNotRegistered(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('No plugin implements countUsersInGroup in this LDAP Backend.');

		$pluginManager = $this->getGroupPluginManager();
		$pluginManager->countUsersInGroup('foo', 'bar');
	}

	public function testgetGroupDetails(): void {
		$pluginManager = $this->getGroupPluginManager();

		$plugin = $this->getMockBuilder('OCA\User_LDAP\Tests\LDAPGroupPluginDummy')
			->setMethods(['respondToActions', 'getGroupDetails'])
			->getMock();

		$plugin->expects($this->any())
			->method('respondToActions')
			->willReturn(GroupInterface::GROUP_DETAILS);

		$plugin->expects($this->once())
			->method('getGroupDetails')
			->with(
				$this->equalTo('gid')
			);

		$pluginManager->register($plugin);
		$pluginManager->getGroupDetails('gid');
	}


	public function testgetGroupDetailsNotRegistered(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('No plugin implements getGroupDetails in this LDAP Backend.');

		$pluginManager = $this->getGroupPluginManager();
		$pluginManager->getGroupDetails('foo');
	}
}
