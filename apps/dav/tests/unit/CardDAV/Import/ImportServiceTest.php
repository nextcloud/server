<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CardDAV\Import;

use OCA\DAV\CardDAV\AddressBookImpl;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\Import\ImportCountEvent;
use OCA\DAV\CardDAV\Import\ImportDisposition;
use OCA\DAV\CardDAV\Import\ImportObjectEvent;
use OCA\DAV\CardDAV\Import\ImportService;
use OCP\Contacts\ContactsImportOptions;
use PHPUnit\Framework\MockObject\MockObject;

class ImportServiceTest extends \Test\TestCase {

	private ImportService $service;
	private AddressBookImpl|MockObject $addressBook;
	private CardDavBackend|MockObject $backend;

	protected function setUp(): void {
		parent::setUp();

		$this->backend = $this->createMock(CardDavBackend::class);
		$this->service = new ImportService($this->backend);
		$this->addressBook = $this->createMock(AddressBookImpl::class);
	}

	public function testImportWithoutUID(): void {
		// Arrange
		// construct stream with two cards that have no UID
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, "BEGIN:VCARD\r\nVERSION:3.0\r\nFN:Erika Mustermann\r\nN:Mustermann;Erika;;;\r\nEND:VCARD\r\n"
			. "BEGIN:VCARD\r\nVERSION:3.0\r\nFN:Max Mustermann\r\nN:Mustermann;Max;;;\r\nEND:VCARD\r\n");
		rewind($stream);
		// construct import options
		$options = new ContactsImportOptions();
		$options->setFormat('vcf');
		$options->setCounts(true);
		$options->setErrors(ContactsImportOptions::ERROR_CONTINUE);

		// Mock address book methods
		$this->addressBook->expects($this->once())
			->method('getKey')
			->willReturn('1');

		// Mock backend methods
		$this->backend->expects($this->never())
			->method('createCard');

		// Act
		$result = iterator_to_array($this->service->import($stream, $this->addressBook, $options), false);

		// Assert
		$this->assertCount(3, $result, 'Import result should contain counts and two items');
		$this->assertInstanceOf(ImportCountEvent::class, $result[0]);
		$this->assertSame(2, $result[0]->vcard);
		foreach ([$result[1], $result[2]] as $event) {
			$this->assertInstanceOf(ImportObjectEvent::class, $event);
			$this->assertNull($event->identifier);
			$this->assertSame(ImportDisposition::Error, $event->disposition);
			$this->assertSame(['One or more objects discovered without a UID'], $event->errors);
		}
	}
}
