<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Tests\Mapping;

use OCA\User_LDAP\Mapping\GroupMapping;
use OCP\IDBConnection;

/**
 * Class GroupMappingTest
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests\Mapping
 */
class GroupMappingTest extends AbstractMappingTestCase {
	public function getMapper(IDBConnection $dbMock) {
		return new GroupMapping($dbMock);
	}
}
