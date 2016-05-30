<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace Test\Files\Storage;

/**
 * Class HomeStorageQuotaTest
 *
 * @group DB
 */
class HomeStorageQuotaTest extends \Test\TestCase {
	/**
	 * Tests that the home storage is not wrapped when no quota exists.
	 */
	function testHomeStorageWrapperWithoutQuota() {
		$user1 = $this->getUniqueID();
		\OC::$server->getUserManager()->createUser($user1, 'test');
		\OC::$server->getConfig()->setUserValue($user1, 'files', 'quota', 'none');
		\OC_User::setUserId($user1);

		\OC_Util::setupFS($user1);

		$userMount = \OC\Files\Filesystem::getMountManager()->find('/' . $user1 . '/');
		$this->assertNotNull($userMount);
		$this->assertNotInstanceOf('\OC\Files\Storage\Wrapper\Quota', $userMount->getStorage());

		// clean up
		\OC_User::setUserId('');
		$user = \OC::$server->getUserManager()->get($user1);
		if ($user !== null) { $user->delete(); }
		\OC::$server->getConfig()->deleteAllUserValues($user1);
		\OC_Util::tearDownFS();
	}

	/**
	 * Tests that the home storage is not wrapped when no quota exists.
	 */
	function testHomeStorageWrapperWithQuota() {
		$user1 = $this->getUniqueID();
		\OC::$server->getUserManager()->createUser($user1, 'test');
		\OC::$server->getConfig()->setUserValue($user1, 'files', 'quota', '1024');
		\OC_User::setUserId($user1);

		\OC_Util::setupFS($user1);

		$userMount = \OC\Files\Filesystem::getMountManager()->find('/' . $user1 . '/');
		$this->assertNotNull($userMount);
		$this->assertTrue($userMount->getStorage()->instanceOfStorage('\OC\Files\Storage\Wrapper\Quota'));

		// ensure that root wasn't wrapped
		$rootMount = \OC\Files\Filesystem::getMountManager()->find('/');
		$this->assertNotNull($rootMount);
		$this->assertNotInstanceOf('\OC\Files\Storage\Wrapper\Quota', $rootMount->getStorage());

		// clean up
		\OC_User::setUserId('');
		$user = \OC::$server->getUserManager()->get($user1);
		if ($user !== null) { $user->delete(); }
		\OC::$server->getConfig()->deleteAllUserValues($user1);
		\OC_Util::tearDownFS();
	}

}
