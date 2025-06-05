<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Service;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Service\ExampleEventService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ExampleEventServiceTest extends TestCase {
	private ExampleEventService $service;

	private CalDavBackend&MockObject $calDavBackend;
	private ISecureRandom&MockObject $random;
	private ITimeFactory&MockObject $time;
	private IAppData&MockObject $appData;
	private IAppConfig&MockObject $appConfig;
	private IL10N&MockObject $l10n;

	protected function setUp(): void {
		parent::setUp();

		$this->calDavBackend = $this->createMock(CalDavBackend::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->time = $this->createMock(ITimeFactory::class);
		$this->appData = $this->createMock(IAppData::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->l10n = $this->createMock(IL10N::class);

		$this->l10n->method('t')
			->willReturnArgument(0);

		$this->service = new ExampleEventService(
			$this->calDavBackend,
			$this->random,
			$this->time,
			$this->appData,
			$this->appConfig,
			$this->l10n,
		);
	}

	public static function provideCustomEventData(): array {
		return [
			[file_get_contents(__DIR__ . '/../test_fixtures/example-event.ics')],
			[file_get_contents(__DIR__ . '/../test_fixtures/example-event-with-attendees.ics')],
		];
	}

	/** @dataProvider provideCustomEventData */
	public function testCreateExampleEventWithCustomEvent($customEventIcs): void {
		$this->appConfig->expects(self::once())
			->method('getValueBool')
			->with('dav', 'create_example_event', true)
			->willReturn(true);

		$exampleEventFolder = $this->createMock(ISimpleFolder::class);
		$this->appData->expects(self::once())
			->method('getFolder')
			->with('example_event')
			->willReturn($exampleEventFolder);
		$exampleEventFile = $this->createMock(ISimpleFile::class);
		$exampleEventFolder->expects(self::once())
			->method('getFile')
			->with('example_event.ics')
			->willReturn($exampleEventFile);
		$exampleEventFile->expects(self::once())
			->method('getContent')
			->willReturn($customEventIcs);

		$this->random->expects(self::once())
			->method('generate')
			->with(32, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
			->willReturn('RANDOM-UID');

		$now = new \DateTimeImmutable('2025-01-21T00:00:00Z');
		$this->time->expects(self::exactly(2))
			->method('now')
			->willReturn($now);

		$expectedIcs = file_get_contents(__DIR__ . '/../test_fixtures/example-event-expected.ics');
		$this->calDavBackend->expects(self::once())
			->method('createCalendarObject')
			->with(1000, 'RANDOM-UID.ics', $expectedIcs);

		$this->service->createExampleEvent(1000);
	}

	public function testCreateExampleEventWithDefaultEvent(): void {
		$this->appConfig->expects(self::once())
			->method('getValueBool')
			->with('dav', 'create_example_event', true)
			->willReturn(true);

		$this->appData->expects(self::once())
			->method('getFolder')
			->with('example_event')
			->willThrowException(new NotFoundException());

		$this->random->expects(self::once())
			->method('generate')
			->with(32, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
			->willReturn('RANDOM-UID');

		$now = new \DateTimeImmutable('2025-01-21T00:00:00Z');
		$this->time->expects(self::exactly(3))
			->method('now')
			->willReturn($now);

		$expectedIcs = file_get_contents(__DIR__ . '/../test_fixtures/example-event-default-expected.ics');
		$this->calDavBackend->expects(self::once())
			->method('createCalendarObject')
			->with(1000, 'RANDOM-UID.ics', $expectedIcs);

		$this->service->createExampleEvent(1000);
	}

	public function testCreateExampleWhenDisabled(): void {
		$this->appConfig->expects(self::once())
			->method('getValueBool')
			->with('dav', 'create_example_event', true)
			->willReturn(false);

		$this->calDavBackend->expects(self::never())
			->method('createCalendarObject');

		$this->service->createExampleEvent(1000);
	}

	/** @dataProvider provideCustomEventData */
	public function testGetExampleEventWithCustomEvent($customEventIcs): void {
		$exampleEventFolder = $this->createMock(ISimpleFolder::class);
		$this->appData->expects(self::once())
			->method('getFolder')
			->with('example_event')
			->willReturn($exampleEventFolder);
		$exampleEventFile = $this->createMock(ISimpleFile::class);
		$exampleEventFolder->expects(self::once())
			->method('getFile')
			->with('example_event.ics')
			->willReturn($exampleEventFile);
		$exampleEventFile->expects(self::once())
			->method('getContent')
			->willReturn($customEventIcs);

		$this->random->expects(self::once())
			->method('generate')
			->with(32, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
			->willReturn('RANDOM-UID');

		$now = new \DateTimeImmutable('2025-01-21T00:00:00Z');
		$this->time->expects(self::exactly(2))
			->method('now')
			->willReturn($now);

		$expectedIcs = file_get_contents(__DIR__ . '/../test_fixtures/example-event-expected.ics');
		$actualIcs = $this->service->getExampleEvent()->getIcs();
		$this->assertEquals($expectedIcs, $actualIcs);
	}

	public function testGetExampleEventWithDefault(): void {
		$this->appData->expects(self::once())
			->method('getFolder')
			->with('example_event')
			->willThrowException(new NotFoundException());

		$this->random->expects(self::once())
			->method('generate')
			->with(32, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
			->willReturn('RANDOM-UID');

		$now = new \DateTimeImmutable('2025-01-21T00:00:00Z');
		$this->time->expects(self::exactly(3))
			->method('now')
			->willReturn($now);

		$expectedIcs = file_get_contents(__DIR__ . '/../test_fixtures/example-event-default-expected.ics');
		$actualIcs = $this->service->getExampleEvent()->getIcs();
		$this->assertEquals($expectedIcs, $actualIcs);
	}
}
