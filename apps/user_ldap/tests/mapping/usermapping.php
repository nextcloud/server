<?php
/**
* Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.com>
* This file is licensed under the Affero General Public License version 3 or
* later.
* See the COPYING-README file.
*/

namespace OCA\user_ldap\tests\mapping;

use OCA\User_LDAP\Mapping\UserMapping;

class Test_UserMapping extends AbstractMappingTest {
	public function getMapper(\OCP\IDBConnection $dbMock) {
		return new UserMapping($dbMock);
	}
}
