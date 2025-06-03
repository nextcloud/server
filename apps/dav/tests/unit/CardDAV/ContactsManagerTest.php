<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\CardDAV;

use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\ContactsManager;
use OCA\DAV\Db\PropertyMapper;
use OCP\Contacts\IManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ContactsManagerTest extends TestCase {
	public function test(): void {
		/** @var IManager&MockObject $cm */
		$cm = $this->createMock(IManager::class);
		$cm->expects($this->exactly(2))->method('registerAddressBook');
		$urlGenerator = $this->createMock(IURLGenerator::class);
		/** @var CardDavBackend&MockObject $backEnd */
		$backEnd = $this->createMock(CardDavBackend::class);
		$backEnd->method('getAddressBooksForUser')->willReturn([
			['{DAV:}displayname' => 'Test address book', 'uri' => 'default'],
		]);
		$propertyMapper = $this->createMock(PropertyMapper::class);

		$l = $this->createMock(IL10N::class);
		$app = new ContactsManager($backEnd, $l, $propertyMapper);
		$app->setupContactsProvider($cm, 'user01', $urlGenerator);
	}
}
