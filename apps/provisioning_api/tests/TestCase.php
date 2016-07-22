<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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

use OCP\IUser;
use OCP\IUserManager;
use OCP\IGroupManager;

abstract class TestCase extends \Test\TestCase {

	/** @var IUser[] */
	protected $users = array();

	/** @var IUserManager */
	protected $userManager;

	/** @var IGroupManager */
	protected $groupManager;

	protected function setUp() {
		parent::setUp();

		$this->userManager = \OC::$server->getUserManager();
		$this->groupManager = \OC::$server->getGroupManager();
		$this->groupManager->createGroup('admin');
	}

	/**
	 * Generates a temp user
	 * @param int $num number of users to generate
	 * @return IUser[]|IUser
	 */
	protected function generateUsers($num = 1) {
		$users = array();
		for ($i = 0; $i < $num; $i++) {
			$user = $this->userManager->createUser($this->getUniqueID(), 'password');
			$this->users[] = $user;
			$users[] = $user;
		}
		return count($users) == 1 ? reset($users) : $users;
	}

	protected function tearDown() {
		foreach($this->users as $user) {
			$user->delete();
		}

		$this->groupManager->get('admin')->delete();
		parent::tearDown();
	}
}
