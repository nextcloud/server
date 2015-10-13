<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Tom Needham <tom@owncloud.com>
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

use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\IUserSession;

class GroupsTest extends TestCase {
	
	/** @var IUserManager */
	protected $userManager;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var IUserSession */
	protected $userSession;

	protected function setup() {
		parent::setup();

		$this->userManager = \OC::$server->getUserManager();
		$this->groupManager = \OC::$server->getGroupManager();
		$this->userSession = \OC::$server->getUserSession();
		$this->api = new \OCA\Provisioning_API\Groups(
			$this->groupManager,
			$this->userSession
		);
	}

	public function testGetGroups() {
		$groups = [];
		$id = $this->getUniqueID();

		for ($i=0; $i < 10; $i++) {
			$groups[] = $this->groupManager->createGroup($id . '_' . $i);
		}

		$_GET = [];
		$result = $this->api->getGroups([]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertCount(count($this->groupManager->search('')), $result->getData()['groups']);
		$this->assertContains('admin', $result->getData()['groups']);
		foreach ($groups as $group) {
			$this->assertContains($group->getGID(), $result->getData()['groups']);
		}

		$_GET = [
			'search' => $id,
			'limit' => 5,
			'offset' => 2
		];
		$result = $this->api->getGroups([]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertCount(5, $result->getData()['groups']);
		foreach (array_splice($groups, 2, 5) as $group) {
			$this->assertContains($group->getGID(), $result->getData()['groups']);
		}

		foreach ($groups as $group) {
			$group->delete();
		}
	}

	public function testGetGroupAsUser() {

		$users = $this->generateUsers(2);
		$this->userSession->setUser($users[0]);

		$group = $this->groupManager->createGroup($this->getUniqueID());
		$group->addUser($users[1]);

		$result = $this->api->getGroup(array(
			'groupid' => $group->getGID(),
		));

		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(\OCP\API::RESPOND_UNAUTHORISED, $result->getStatusCode());

	}

	public function testGetGroupAsSubadmin() {

		$users = $this->generateUsers(2);
		$this->userSession->setUser($users[0]);

		$group = $this->groupManager->createGroup($this->getUniqueID());
		$group->addUser($users[0]);
		$group->addUser($users[1]);

		\OC_SubAdmin::createSubAdmin($users[0]->getUID(), $group->getGID());

		$result = $this->api->getGroup([
			'groupid' => $group->getGID(),
		]);

		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals(1, sizeof($result->getData()), 'Asserting the result data array only has the "users" key');
		$this->assertArrayHasKey('users', $result->getData());
		$resultData = $result->getData();
		$resultData = $resultData['users'];

		$users = array_map(function($user) {
			return $user->getUID();
		}, $users);

		sort($users);
		sort($resultData);
		$this->assertEquals($users, $resultData);

	}

	public function testGetGroupAsIrrelevantSubadmin() {

		$users = $this->generateUsers(2);
		$this->userSession->setUser($users[0]);

		$group1 = $this->groupManager->createGroup($this->getUniqueID());
		$group2 = $this->groupManager->createGroup($this->getUniqueID());
		$group1->addUser($users[1]);
		$group2->addUser($users[0]);

		\OC_SubAdmin::createSubAdmin($users[0]->getUID(), $group2->getGID());

		$result = $this->api->getGroup([
			'groupid' => $group1->getGID(),
		]);

		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(\OCP\API::RESPOND_UNAUTHORISED, $result->getStatusCode());

	}

	public function testGetGroupAsAdmin() {

		$users = $this->generateUsers(2);
		$this->userSession->setUser($users[0]);

		$group = $this->groupManager->createGroup($this->getUniqueID());

		$group->addUser($users[1]);
		$this->groupManager->get('admin')->addUser($users[0]);

		$result = $this->api->getGroup([
			'groupid' => $group->getGID(),
		]);

		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals(['users' => [$users[1]->getUID()]], $result->getData());

	}

	public function testGetGroupNonExisting() {
		$result = $this->api->getGroup([
			'groupid' => $this->getUniqueId()
		]);

		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(\OCP\API::RESPOND_NOT_FOUND, $result->getStatusCode());
		$this->assertEquals('The requested group could not be found', $result->getMeta()['message']);
	}

	public function testGetSubAdminsOfGroup() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$this->groupManager->get('admin')->addUser($user1);
		$group1 = $this->groupManager->createGroup($this->getUniqueID());
		\OC_SubAdmin::createSubAdmin($user2->getUID(), $group1->getGID());
		$result = $this->api->getSubAdminsOfGroup([
			'groupid' => $group1->getGID(),
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals($user2->getUID(), reset($data));
		$group1->delete();

		$user1 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$this->groupManager->get('admin')->addUser($user1);
		$result = $this->api->getSubAdminsOfGroup([
			'groupid' => $this->getUniqueID(),
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
	}

	public function testAddGroupEmptyGroup() {
		$_POST = [];
		$result = $this->api->addGroup([]);

		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
		$this->assertEquals('Invalid group name', $result->getMeta()['message']);
	}

	public function testAddGroupExistingGroup() {
		$group = $this->groupManager->createGroup($this->getUniqueID());

		$_POST = [
			'groupid' => $group->getGID()
		];
		$result = $this->api->addGroup([]);

		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(102, $result->getStatusCode());

		$group->delete();
	}

	public function testAddGroup() {
		$group = $this->getUniqueId();

		$_POST = [ 
			'groupid' => $group
		];

		$result = $this->api->addGroup([]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertTrue($this->groupManager->groupExists($group));

		$this->groupManager->get($group)->delete();
	}

	public function testDeleteGroupNonExisting() {
		$group = $this->getUniqueId();

		$result = $this->api->deleteGroup([
			'groupid' => $group
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
	}

	public function testDeleteAdminGroup() {
		$result = $this->api->deleteGroup([
			'groupid' => 'admin'
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(102, $result->getStatusCode());
	}

	public function testDeleteGroup() {
		$group = $this->groupManager->createGroup($this->getUniqueId());

		$result = $this->api->deleteGroup([
			'groupid' => $group->getGID()
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertFalse($this->groupManager->groupExists($group->getGID()));
	}
}
