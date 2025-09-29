<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests;

use OC\User\Backend;
use OCA\User_LDAP\UserPluginManager;

class UserLDAPPluginTest extends \Test\TestCase {
	private function getUserPluginManager(): UserPluginManager {
		return new UserPluginManager();
	}

	public function testImplementsActions(): void {
		$pluginManager = $this->getUserPluginManager();

		$plugin = $this->getMockBuilder(LDAPUserPluginDummy::class)
			->onlyMethods(['respondToActions'])
			->getMock();

		$plugin->expects($this->any())
			->method('respondToActions')
			->willReturn(Backend::CREATE_USER);

		$plugin2 = $this->getMockBuilder(LDAPUserPluginDummy::class)
			->onlyMethods(['respondToActions'])
			->getMock();

		$plugin2->expects($this->any())
			->method('respondToActions')
			->willReturn(Backend::PROVIDE_AVATAR);

		$pluginManager->register($plugin);
		$pluginManager->register($plugin2);

		$this->assertEquals($pluginManager->getImplementedActions(), Backend::CREATE_USER | Backend::PROVIDE_AVATAR);
		$this->assertTrue($pluginManager->implementsActions(Backend::CREATE_USER));
		$this->assertTrue($pluginManager->implementsActions(Backend::PROVIDE_AVATAR));
	}

	public function testCreateUser(): void {
		$pluginManager = $this->getUserPluginManager();

		$plugin = $this->getMockBuilder(LDAPUserPluginDummy::class)
			->onlyMethods(['respondToActions', 'createUser'])
			->getMock();

		$plugin->expects($this->any())
			->method('respondToActions')
			->willReturn(Backend::CREATE_USER);

		$plugin->expects($this->once())
			->method('createUser')
			->with(
				$this->equalTo('user'),
				$this->equalTo('password')
			);

		$pluginManager->register($plugin);
		$pluginManager->createUser('user', 'password');
	}


	public function testCreateUserNotRegistered(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('No plugin implements createUser in this LDAP Backend.');

		$pluginManager = $this->getUserPluginManager();
		$pluginManager->createUser('foo', 'bar');
	}

	public function testSetPassword(): void {
		$pluginManager = $this->getUserPluginManager();

		$plugin = $this->getMockBuilder(LDAPUserPluginDummy::class)
			->onlyMethods(['respondToActions', 'setPassword'])
			->getMock();

		$plugin->expects($this->any())
			->method('respondToActions')
			->willReturn(Backend::SET_PASSWORD);

		$plugin->expects($this->once())
			->method('setPassword')
			->with(
				$this->equalTo('user'),
				$this->equalTo('password')
			);

		$pluginManager->register($plugin);
		$pluginManager->setPassword('user', 'password');
	}


	public function testSetPasswordNotRegistered(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('No plugin implements setPassword in this LDAP Backend.');

		$pluginManager = $this->getUserPluginManager();
		$pluginManager->setPassword('foo', 'bar');
	}

	public function testGetHome(): void {
		$pluginManager = $this->getUserPluginManager();

		$plugin = $this->getMockBuilder(LDAPUserPluginDummy::class)
			->onlyMethods(['respondToActions', 'getHome'])
			->getMock();

		$plugin->expects($this->any())
			->method('respondToActions')
			->willReturn(Backend::GET_HOME);

		$plugin->expects($this->once())
			->method('getHome')
			->with(
				$this->equalTo('uid')
			);

		$pluginManager->register($plugin);
		$pluginManager->getHome('uid');
	}


	public function testGetHomeNotRegistered(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('No plugin implements getHome in this LDAP Backend.');

		$pluginManager = $this->getUserPluginManager();
		$pluginManager->getHome('foo');
	}

	public function testGetDisplayName(): void {
		$pluginManager = $this->getUserPluginManager();

		$plugin = $this->getMockBuilder(LDAPUserPluginDummy::class)
			->onlyMethods(['respondToActions', 'getDisplayName'])
			->getMock();

		$plugin->expects($this->any())
			->method('respondToActions')
			->willReturn(Backend::GET_DISPLAYNAME);

		$plugin->expects($this->once())
			->method('getDisplayName')
			->with(
				$this->equalTo('uid')
			);

		$pluginManager->register($plugin);
		$pluginManager->getDisplayName('uid');
	}


	public function testGetDisplayNameNotRegistered(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('No plugin implements getDisplayName in this LDAP Backend.');

		$pluginManager = $this->getUserPluginManager();
		$pluginManager->getDisplayName('foo');
	}

	public function testSetDisplayName(): void {
		$pluginManager = $this->getUserPluginManager();

		$plugin = $this->getMockBuilder(LDAPUserPluginDummy::class)
			->onlyMethods(['respondToActions', 'setDisplayName'])
			->getMock();

		$plugin->expects($this->any())
			->method('respondToActions')
			->willReturn(Backend::SET_DISPLAYNAME);

		$plugin->expects($this->once())
			->method('setDisplayName')
			->with(
				$this->equalTo('user'),
				$this->equalTo('password')
			);

		$pluginManager->register($plugin);
		$pluginManager->setDisplayName('user', 'password');
	}


	public function testSetDisplayNameNotRegistered(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('No plugin implements setDisplayName in this LDAP Backend.');

		$pluginManager = $this->getUserPluginManager();
		$pluginManager->setDisplayName('foo', 'bar');
	}

	public function testCanChangeAvatar(): void {
		$pluginManager = $this->getUserPluginManager();

		$plugin = $this->getMockBuilder(LDAPUserPluginDummy::class)
			->onlyMethods(['respondToActions', 'canChangeAvatar'])
			->getMock();

		$plugin->expects($this->any())
			->method('respondToActions')
			->willReturn(Backend::PROVIDE_AVATAR);

		$plugin->expects($this->once())
			->method('canChangeAvatar')
			->with(
				$this->equalTo('uid')
			);

		$pluginManager->register($plugin);
		$pluginManager->canChangeAvatar('uid');
	}


	public function testCanChangeAvatarNotRegistered(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('No plugin implements canChangeAvatar in this LDAP Backend.');

		$pluginManager = $this->getUserPluginManager();
		$pluginManager->canChangeAvatar('foo');
	}

	public function testCountUsers(): void {
		$pluginManager = $this->getUserPluginManager();

		$plugin = $this->getMockBuilder(LDAPUserPluginDummy::class)
			->onlyMethods(['respondToActions', 'countUsers'])
			->getMock();

		$plugin->expects($this->any())
			->method('respondToActions')
			->willReturn(Backend::COUNT_USERS);

		$plugin->expects($this->once())
			->method('countUsers');

		$pluginManager->register($plugin);
		$pluginManager->countUsers();
	}


	public function testCountUsersNotRegistered(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('No plugin implements countUsers in this LDAP Backend.');

		$pluginManager = $this->getUserPluginManager();
		$pluginManager->countUsers();
	}

	public function testDeleteUser(): void {
		$pluginManager = $this->getUserPluginManager();

		$plugin = $this->getMockBuilder(LDAPUserPluginDummy::class)
			->onlyMethods(['respondToActions', 'canDeleteUser','deleteUser'])
			->getMock();

		$plugin->expects($this->any())
			->method('respondToActions')
			->willReturn(0);

		$plugin->expects($this->any())
			->method('canDeleteUser')
			->willReturn(true);

		$plugin->expects($this->once())
			->method('deleteUser')
			->with(
				$this->equalTo('uid')
			);

		$this->assertFalse($pluginManager->canDeleteUser());
		$pluginManager->register($plugin);
		$this->assertTrue($pluginManager->canDeleteUser());
		$pluginManager->deleteUser('uid');
	}


	public function testDeleteUserNotRegistered(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('No plugin implements deleteUser in this LDAP Backend.');

		$pluginManager = $this->getUserPluginManager();
		$pluginManager->deleteUser('foo');
	}
}
