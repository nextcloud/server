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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Reader;

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

	public static function validationProvider(): array {
		return [
			'default validation' => [ContactsImportOptions::VALIDATE_SKIP],
			'no validation' => [ContactsImportOptions::VALIDATE_NONE],
			'strict validation' => [ContactsImportOptions::VALIDATE_FAIL],
		];
	}

	#[DataProvider('validationProvider')]
	public function testImportWithoutUID(int $validation): void {
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, "BEGIN:VCARD\r\nVERSION:3.0\r\nFN:Erika Mustermann\r\nN:Mustermann;Erika;;;\r\nEND:VCARD\r\n"
			. "BEGIN:VCARD\r\nVERSION:3.0\r\nUID:existing-uid\r\nFN:Existing Contact\r\nN:Contact;Existing;;;\r\nEND:VCARD\r\n"
			. "BEGIN:VCARD\r\nVERSION:3.0\r\nFN:Max Mustermann\r\nN:Mustermann;Max;;;\r\nEND:VCARD\r\n");
		rewind($stream);
		$options = new ContactsImportOptions();
		$options->setCounts(true);
		$options->setValidate($validation);

		$this->addressBook->expects($this->once())
			->method('getKey')
			->willReturn('1');

		$lookedUpUIDs = [];
		$this->backend->expects($this->exactly(3))
			->method('getCardByUID')
			->willReturnCallback(function (int $addressBookId, string $uid) use (&$lookedUpUIDs): ?array {
				$this->assertSame(1, $addressBookId);
				$this->assertNotSame('', $uid);
				$lookedUpUIDs[] = $uid;
				return null;
			});

		$cards = [];
		$this->backend->expects($this->exactly(3))
			->method('createCard')
			->willReturnCallback(function (int $addressBookId, string $uri, string $data) use (&$cards): string {
				$this->assertSame(1, $addressBookId);
				$cards[] = Reader::read($data);
				return 'etag';
			});
		$this->backend->expects($this->never())->method('updateCard');

		try {
			$result = iterator_to_array($this->service->import($stream, $this->addressBook, $options), false);
		} finally {
			fclose($stream);
		}

		$this->assertCount(4, $result);
		$this->assertInstanceOf(ImportCountEvent::class, $result[0]);
		$this->assertSame(3, $result[0]->vcard);
		$this->assertCount(3, array_unique($lookedUpUIDs));
		$this->assertSame('existing-uid', $lookedUpUIDs[1]);
		$this->assertSame(['Erika Mustermann', 'Existing Contact', 'Max Mustermann'], array_map(fn ($card) => (string)$card->FN, $cards));
		foreach ($cards as $index => $card) {
			$this->assertSame($lookedUpUIDs[$index], (string)$card->UID);
			$event = $result[$index + 1];
			$this->assertInstanceOf(ImportObjectEvent::class, $event);
			$this->assertSame($lookedUpUIDs[$index], $event->identifier);
			$this->assertSame(ImportDisposition::Created, $event->disposition);
		}
	}
}
