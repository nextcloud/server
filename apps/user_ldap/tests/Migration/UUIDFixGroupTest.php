<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests\Migration;

use OCA\User_LDAP\Group_Proxy;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Migration\UUIDFixGroup;

/**
 * Class UUIDFixGroupTest
 *
 * @package OCA\Group_LDAP\Tests\Migration
 * @group DB
 */
class UUIDFixGroupTest extends AbstractUUIDFixTestCase {
	protected function setUp(): void {
		$this->isUser = false;
		parent::setUp();

		$this->mapper = $this->createMock(GroupMapping::class);
		$this->proxy = $this->createMock(Group_Proxy::class);

		$this->instantiateJob(UUIDFixGroup::class);
	}
}
