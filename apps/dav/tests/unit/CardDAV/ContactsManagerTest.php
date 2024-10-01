<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\CardDAV;

use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\ContactsManager;
use OCP\Contacts\IManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use Test\TestCase;

class ContactsManagerTest extends TestCase {
	public function test(): void {
		/** @var IManager | \PHPUnit\Framework\MockObject\MockObject $cm */
		$cm = $this->getMockBuilder(IManager::class)->disableOriginalConstructor()->getMock();
		$cm->expects($this->exactly(2))->method('registerAddressBook');
		$urlGenerator = $this->getMockBuilder(IURLGenerator::class)->disableOriginalConstructor()->getMock();
		/** @var CardDavBackend | \PHPUnit\Framework\MockObject\MockObject $backEnd */
		$backEnd = $this->getMockBuilder(CardDavBackend::class)->disableOriginalConstructor()->getMock();
		$backEnd->method('getAddressBooksForUser')->willReturn([
			['{DAV:}displayname' => 'Test address book', 'uri' => 'default'],
		]);

		$l = $this->createMock(IL10N::class);
		$app = new ContactsManager($backEnd, $l);
		$app->setupContactsProvider($cm, 'user01', $urlGenerator);
	}
}
