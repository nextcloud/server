<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Service;

use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\Service\ExampleContactService;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use Test\TestCase;

class ExampleContactServiceTest extends TestCase {
	protected ExampleContactService $service;
	protected CardDavBackend&MockObject $cardDav;
	protected IAppManager&MockObject $appManager;
	protected IAppDataFactory&MockObject $appDataFactory;
	protected LoggerInterface&MockObject $logger;
	protected IAppConfig&MockObject $appConfig;
	protected IAppData&MockObject $appData;

	protected function setUp(): void {
		parent::setUp();

		$this->cardDav = $this->createMock(CardDavBackend::class);
		$this->appDataFactory = $this->createMock(IAppDataFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->appData = $this->createMock(IAppData::class);
		$this->appDataFactory->method('get')
			->with('dav')
			->willReturn($this->appData);

		$this->service = new ExampleContactService(
			$this->appDataFactory,
			$this->appConfig,
			$this->logger,
			$this->cardDav,
		);
	}

	public function testCreateDefaultContactWithInvalidCard(): void {
		// Invalid vCard missing required FN property
		$vcardContent = "BEGIN:VCARD\nVERSION:3.0\nEND:VCARD";
		$this->appConfig->method('getAppValueBool')
			->with('enableDefaultContact', true)
			->willReturn(true);
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$file->method('getContent')->willReturn($vcardContent);
		$folder->method('getFile')->willReturn($file);
		$this->appData->method('getFolder')->willReturn($folder);

		$this->logger->expects($this->once())
			->method('error')
			->with('Default contact is invalid', $this->anything());

		$this->cardDav->expects($this->never())
			->method('createCard');

		$this->service->createDefaultContact(123);
	}

	public function testUidAndRevAreUpdated(): void {
		$originalUid = 'original-uid';
		$originalRev = '20200101T000000Z';
		$vcardContent = "BEGIN:VCARD\nVERSION:3.0\nFN:Test User\nUID:$originalUid\nREV:$originalRev\nEND:VCARD";

		$this->appConfig->method('getAppValueBool')
			->with('enableDefaultContact', true)
			->willReturn(true);
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$file->method('getContent')->willReturn($vcardContent);
		$folder->method('getFile')->willReturn($file);
		$this->appData->method('getFolder')->willReturn($folder);

		$capturedCardData = null;
		$this->cardDav->expects($this->once())
			->method('createCard')
			->with(
				$this->anything(),
				$this->anything(),
				$this->callback(function ($cardData) use (&$capturedCardData) {
					$capturedCardData = $cardData;
					return true;
				}),
				$this->anything()
			)->willReturn(null);

		$this->service->createDefaultContact(123);

		$vcard = \Sabre\VObject\Reader::read($capturedCardData);
		$this->assertNotEquals($originalUid, $vcard->UID->getValue());
		$this->assertTrue(Uuid::isValid($vcard->UID->getValue()));
		$this->assertNotEquals($originalRev, $vcard->REV->getValue());
	}

	public function testDefaultContactFileDoesNotExist(): void {
		$this->appConfig->method('getAppValueBool')
			->with('enableDefaultContact', true)
			->willReturn(true);
		$this->appData->method('getFolder')->willThrowException(new NotFoundException());

		$this->cardDav->expects($this->never())
			->method('createCard');

		$this->service->createDefaultContact(123);
	}

	public function testUidAndRevAreAddedIfMissing(): void {
		$vcardContent = "BEGIN:VCARD\nVERSION:3.0\nFN:Test User\nEND:VCARD";

		$this->appConfig->method('getAppValueBool')
			->with('enableDefaultContact', true)
			->willReturn(true);
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$file->method('getContent')->willReturn($vcardContent);
		$folder->method('getFile')->willReturn($file);
		$this->appData->method('getFolder')->willReturn($folder);

		$capturedCardData = 'new-card-data';

		$this->cardDav
			->expects($this->once())
			->method('createCard')
			->with(
				$this->anything(),
				$this->anything(),
				$this->callback(function ($cardData) use (&$capturedCardData) {
					$capturedCardData = $cardData;
					return true;
				}),
				$this->anything()
			);

		$this->service->createDefaultContact(123);
		$vcard = \Sabre\VObject\Reader::read($capturedCardData);

		$this->assertNotNull($vcard->REV);
		$this->assertNotNull($vcard->UID);
		$this->assertTrue(Uuid::isValid($vcard->UID->getValue()));
	}

	public function testDefaultContactIsNotCreatedIfEnabled(): void {
		$this->appConfig->method('getAppValueBool')
			->with('enableDefaultContact', true)
			->willReturn(false);
		$this->logger->expects($this->never())
			->method('error');
		$this->cardDav->expects($this->never())
			->method('createCard');

		$this->service->createDefaultContact(123);
	}

	public static function provideDefaultContactEnableData(): array {
		return [[true], [false]];
	}

	/** @dataProvider provideDefaultContactEnableData */
	public function testIsDefaultContactEnabled(bool $enabled): void {
		$this->appConfig->expects(self::once())
			->method('getAppValueBool')
			->with('enableDefaultContact', true)
			->willReturn($enabled);

		$this->assertEquals($enabled, $this->service->isDefaultContactEnabled());
	}

	/** @dataProvider provideDefaultContactEnableData */
	public function testSetDefaultContactEnabled(bool $enabled): void {
		$this->appConfig->expects(self::once())
			->method('setAppValueBool')
			->with('enableDefaultContact', $enabled);

		$this->service->setDefaultContactEnabled($enabled);
	}
}
