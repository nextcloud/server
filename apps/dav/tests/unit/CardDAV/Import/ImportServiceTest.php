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
use Sabre\VObject\Component\VCard;
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
		$cardsToImport = [
			new VCard(['VERSION' => '3.0', 'FN' => 'Erika Mustermann', 'N' => 'Mustermann;Erika;;;'], false),
			new VCard(['VERSION' => '3.0', 'UID' => 'existing-uid', 'FN' => 'Existing Contact', 'N' => 'Contact;Existing;;;'], false),
			new VCard(['VERSION' => '3.0', 'FN' => 'Max Mustermann', 'N' => 'Mustermann;Max;;;'], false),
		];
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

		$generator = static function () use ($cardsToImport): \Generator {
			yield 'counts' => ['VCARD' => count($cardsToImport)];
			yield from $cardsToImport;
		};
		$result = iterator_to_array($this->service->importProcess(null, $this->addressBook, $options, $generator), false);

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

	public function testImportTextWithCardWithoutUID(): void {
		$vcf = <<<'VCF'
BEGIN:VCARD
VERSION:3.0
FN:Erika Mustermann
N:Mustermann;Erika;;;
END:VCARD
BEGIN:VCARD
VERSION:3.0
UID:existing-uid
FN:Max Mustermann
N:Mustermann;Max;;;
END:VCARD
VCF;
		$source = fopen('php://temp', 'r+');
		fwrite($source, $vcf);
		rewind($source);

		$options = new ContactsImportOptions();
		$this->addressBook->method('getKey')->willReturn('1');

		$displayNames = [];
		$this->backend->expects($this->exactly(2))
			->method('createCard')
			->willReturnCallback(function (int $addressBookId, string $uri, string $data) use (&$displayNames): string {
				$this->assertSame(1, $addressBookId);
				$displayNames[] = (string)Reader::read($data)->FN;
				return 'etag';
			});

		$result = iterator_to_array($this->service->import($source, $this->addressBook, $options), false);
		fclose($source);

		$this->assertCount(2, $result);
		$this->assertSame(['Erika Mustermann', 'Max Mustermann'], $displayNames);
		$this->assertSame('existing-uid', $result[1]->identifier);
	}
}
