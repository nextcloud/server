<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Tests\Mapping;

use OCA\User_LDAP\Mapping\GroupMapping;
use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\IDBConnection;

/**
 * Class GroupMappingTest
 *
 *
 * @package OCA\User_LDAP\Tests\Mapping
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class GroupMappingTest extends AbstractMappingTestCase {
	public function getMapper(IDBConnection $dbMock, ICacheFactory $cacheFactory, IAppConfig $appConfig): GroupMapping {
		return new GroupMapping($dbMock, $cacheFactory, $appConfig, true);
	}
}
