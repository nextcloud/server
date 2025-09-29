<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests\Migration;

use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\Migration\UUIDFixUser;
use OCA\User_LDAP\User_Proxy;

/**
 * Class UUIDFixUserTest
 *
 * @package OCA\User_LDAP\Tests\Migration
 * @group DB
 */
class UUIDFixUserTest extends AbstractUUIDFixTestCase {
	protected function setUp(): void {
		$this->isUser = true;
		parent::setUp();

		$this->mapper = $this->createMock(UserMapping::class);
		$this->proxy = $this->createMock(User_Proxy::class);

		$this->instantiateJob(UUIDFixUser::class);
	}
}
