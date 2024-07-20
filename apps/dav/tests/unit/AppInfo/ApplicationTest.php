<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\AppInfo;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\ContactsManager;
use Test\TestCase;

/**
 * Class ApplicationTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\Unit\AppInfo
 */
class ApplicationTest extends TestCase {
	public function test(): void {
		$app = new Application();
		$c = $app->getContainer();

		// assert service instances in the container are properly setup
		$s = $c->query(ContactsManager::class);
		$this->assertInstanceOf(ContactsManager::class, $s);
		$s = $c->query(CardDavBackend::class);
		$this->assertInstanceOf(CardDavBackend::class, $s);
	}
}
