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
use OCP\IAddressBook;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ContactsManagerTest extends TestCase {
	private IManager&MockObject $contactsManager;
	private IURLGenerator&MockObject $urlGenerator;
	private CardDavBackend&MockObject $backend;
	private PropertyMapper&MockObject $propertyMapper;
	private IL10N&MockObject $l10n;
	private ContactsManager $manager;

	protected function setUp(): void {
		parent::setUp();

		$this->contactsManager = $this->createMock(IManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->backend = $this->createMock(CardDavBackend::class);
		$this->propertyMapper = $this->createMock(PropertyMapper::class);
		$this->l10n = $this->createMock(IL10N::class);

		$this->backend->method('getAddressBooksForUser')
			->willReturnCallback(static fn (string $principalUri): array => match ($principalUri) {
				'principals/users/user01' => [
					['id' => 1, 'uri' => 'default', 'principaluri' => $principalUri, '{DAV:}displayname' => 'Test address book'],
				],
				'principals/system/system' => [
					['id' => 2, 'uri' => 'system', 'principaluri' => $principalUri, '{DAV:}displayname' => 'System address book'],
				],
				default => [],
			});

		$this->manager = new ContactsManager($this->backend, $this->l10n, $this->propertyMapper);
	}

	public function testSetupContactsProvider(): void {
		$registered = [];
		$this->contactsManager->expects($this->exactly(2))
			->method('registerAddressBook')
			->willReturnCallback(function (IAddressBook $addressBook) use (&$registered): void {
				$registered[] = $addressBook->getUri();
			});

		$this->manager->setupContactsProvider($this->contactsManager, 'user01', $this->urlGenerator);

		$this->assertEquals(['default', 'system'], $registered);
	}

	public function testSetupSystemContactsProvider(): void {
		$registered = [];
		$this->contactsManager->expects($this->exactly(1))
			->method('registerAddressBook')
			->willReturnCallback(function (IAddressBook $addressBook) use (&$registered): void {
				$registered[] = $addressBook->getUri();
			});

		$this->manager->setupSystemContactsProvider($this->contactsManager, 'user01', $this->urlGenerator);

		$this->assertEquals(['system'], $registered);
	}
}
