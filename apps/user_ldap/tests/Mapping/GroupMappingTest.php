<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Tests\Mapping;

use OCA\User_LDAP\Mapping\GroupMapping;

/**
 * Class GroupMappingTest
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests\Mapping
 */
class GroupMappingTest extends AbstractMappingTest {
	public function getMapper(\OCP\IDBConnection $dbMock) {
		return new GroupMapping($dbMock);
	}
}
