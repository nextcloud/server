<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Tests\Mapping;

use OCA\User_LDAP\Mapping\UserMapping;
use OCP\IDBConnection;
use OCP\Support\Subscription\IAssertion;

/**
 * Class UserMappingTest
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests\Mapping
 */
class UserMappingTest extends AbstractMappingTestCase {
	public function getMapper(IDBConnection $dbMock) {
		return new UserMapping($dbMock, $this->createMock(IAssertion::class));
	}
}
