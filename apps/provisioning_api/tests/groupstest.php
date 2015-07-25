<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Tom Needham <tom@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\Provisioning_API\Tests;

class GroupsTest extends TestCase {
	
	protected function setup() {
		parent::setup();

		$this->userManager = \OC::$server->getUserManager();
		$this->groupManager = \OC::$server->getGroupManager();
		$this->api = new \OCA\Provisioning_API\Groups($this->groupManager);
	}

	public function testGetGroupAsUser() {

		$users = $this->generateUsers(2);
		self::loginAsUser($users[0]);

		$group = $this->groupManager->createGroup($this->getUniqueID());
		$group->addUser($this->userManager->get($users[1]));

		$result = $this->api->getGroup(array(
			'groupid' => $group->getGID(),
		));

		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(\OCP\API::RESPOND_UNAUTHORISED, $result->getStatusCode());

	}

	public function testGetGroupAsSubadmin() {

		$users = $this->generateUsers(2);
		self::loginAsUser($users[0]);

		$group = $this->groupManager->createGroup($this->getUniqueID());
		$group->addUser($this->userManager->get($users[0]));
		$group->addUser($this->userManager->get($users[1]));

		\OC_SubAdmin::createSubAdmin($users[0], $group->getGID());

		$result = $this->api->getGroup(array(
			'groupid' => $group->getGID(),
		));

		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals(1, sizeof($result->getData()), 'Asserting the result data array only has the "users" key');
		$this->assertArrayHasKey('users', $result->getData());
		$resultData = $result->getData();
		$resultData = $resultData['users'];

		sort($users);
		sort($resultData);
		$this->assertEquals($users, $resultData);

	}

	public function testGetGroupAsIrrelevantSubadmin() {

		$users = $this->generateUsers(2);
		self::loginAsUser($users[0]);

		$group1 = $this->groupManager->createGroup($this->getUniqueID());
		$group2 = $this->groupManager->createGroup($this->getUniqueID());
		$group1->addUser($this->userManager->get($users[1]));
		$group2->addUser($this->userManager->get($users[0]));

		\OC_SubAdmin::createSubAdmin($users[0], $group2->getGID());

		$result = $this->api->getGroup(array(
			'groupid' => $group1->getGID(),
		));

		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(\OCP\API::RESPOND_UNAUTHORISED, $result->getStatusCode());

	}

	public function testGetGroupAsAdmin() {

		$users = $this->generateUsers(2);
		self::loginAsUser($users[0]);

		$group = $this->groupManager->createGroup($this->getUniqueID());

		$group->addUser($this->userManager->get($users[1]));
		$this->groupManager->get('admin')->addUser($this->userManager->get($users[0]));

		$result = $this->api->getGroup(array(
			'groupid' => $group->getGID(),
		));

		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals(array('users' => array($users[1])), $result->getData());

	}

	public function testGetSubAdminsOfGroup() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		self::loginAsUser($user1);
		$this->groupManager->get('admin')->addUser($this->userManager->get($user1));
		$group1 = $this->groupManager->createGroup($this->getUniqueID());
		\OC_SubAdmin::createSubAdmin($user2, $group1->getGID());
		$result = $this->api->getSubAdminsOfGroup(array(
			'groupid' => $group1->getGID(),
		));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals($user2, reset($data));
		$group1->delete();

		$user1 = $this->generateUsers();
		self::loginAsUser($user1);
		$this->groupManager->get('admin')->addUser($this->userManager->get($user1));
		$result = $this->api->getSubAdminsOfGroup(array(
			'groupid' => $this->getUniqueID(),
		));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
	}
}
