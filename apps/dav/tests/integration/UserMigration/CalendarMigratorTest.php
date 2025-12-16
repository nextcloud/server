<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\integration\UserMigration;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarImpl;
use OCA\DAV\UserMigration\CalendarMigrator;
use OCP\AppFramework\App;
use OCP\Calendar\IManager as ICalendarManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use Sabre\VObject\UUIDUtil;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class CalendarMigratorTest extends TestCase {

	private IUserManager $userManager;
	private ICalendarManager $calendarManager;
	private CalDavBackend $calDavBackend;
	private CalendarMigrator $migrator;
	private OutputInterface $output;

	private const ASSETS_DIR = __DIR__ . '/assets/calendars/';
	private const USERS_URI_ROOT = 'principals/users/';

	protected function setUp(): void {
		parent::setUp();

		$app = new App(Application::APP_ID);
		$container = $app->getContainer();

		$this->userManager = $container->get(IUserManager::class);
		$this->calendarManager = $container->get(ICalendarManager::class);
		$this->calDavBackend = $container->get(CalDavBackend::class);
		$this->migrator = $container->get(CalendarMigrator::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	private function createTestUser(): IUser {
		$userId = UUIDUtil::getUUID();
		return $this->userManager->createUser($userId, 'topsecretpassword');
	}

	private function deleteUser(IUser $user): void {
		$user->delete();
	}

	private function getCalendarsForUser(IUser $user): array {
		$principalUri = self::USERS_URI_ROOT . $user->getUID();
		$calendars = $this->calendarManager->getCalendarsForPrincipal($principalUri);
		return array_filter($calendars, fn ($c) => $c instanceof CalendarImpl && !$c->isShared());
	}

	public static function dataAssets(): array {
		$files = scandir(self::ASSETS_DIR);
		if ($files === false) {
			return [];
		}
		$files = array_values(array_diff($files, ['.', '..']));
		return array_map(
			fn (string $filename) => [$filename],
			$files,
		);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataAssets')]
	public function testImportV1(string $filename): void {
		$user = $this->createTestUser();

		try {
			// Setup import source mock for V1 format (.ics files)
			$importSource = $this->createMock(IImportSource::class);

			$icsContent = file_get_contents(self::ASSETS_DIR . $filename);
			$importSource->method('getMigratorVersion')
				->with('calendar')
				->willReturn(1);

			$importSource->method('getFolderListing')
				->with('dav/calendars/')
				->willReturn([$filename]);

			$importSource->method('getFileAsStream')
				->willReturnCallback(function (string $path) use ($filename, $icsContent) {
					if ($path === 'dav/calendars/' . $filename) {
						$stream = fopen('php://temp', 'r+');
						fwrite($stream, $icsContent);
						rewind($stream);
						return $stream;
					}
					throw new \Exception("Unexpected path: $path");
				});

			// Import the calendar
			$this->migrator->import($user, $importSource, $this->output);

			// Verify calendar was created
			$calendars = $this->getCalendarsForUser($user);
			$this->assertCount(1, $calendars, 'Expected one calendar to be created');

			// Verify the calendar URI has the migrated prefix
			$calendar = reset($calendars);
			$expectedUri = 'migrated-' . substr($filename, 0, -4);
			$this->assertEquals($expectedUri, $calendar->getUri());

			// Verify calendar has objects
			$objects = $this->calDavBackend->getCalendarObjects((int)$calendar->getKey());
			$this->assertNotEmpty($objects, 'Expected calendar to have objects');
		} finally {
			$this->deleteUser($user);
		}
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataAssets')]
	public function testImportV2(string $filename): void {
		$user = $this->createTestUser();

		try {
			// Setup import source mock for V2 format (.meta + .data files)
			$importSource = $this->createMock(IImportSource::class);

			$icsContent = file_get_contents(self::ASSETS_DIR . $filename);
			$calendarUri = substr($filename, 0, -4);

			// Extract calendar name and color from ICS for meta
			$calendarName = null;
			$calendarColor = null;
			$lines = explode("\n", $icsContent);
			foreach ($lines as $line) {
				$line = trim($line);
				if (str_starts_with($line, 'X-WR-CALNAME:')) {
					$calendarName = substr($line, 13);
				} elseif (str_starts_with($line, 'X-APPLE-CALENDAR-COLOR:')) {
					$calendarColor = substr($line, 23);
				}
				if (str_starts_with($line, 'BEGIN:VEVENT')
					|| str_starts_with($line, 'BEGIN:VTODO')
					|| str_starts_with($line, 'BEGIN:VJOURNAL')) {
					break;
				}
			}

			$metaContent = json_encode([
				'format' => 'ical',
				'uri' => $calendarUri,
				'label' => $calendarName ?? $calendarUri,
				'color' => $calendarColor ?? '#0082c9',
				'timezone' => null,
			]);

			$importSource->method('getMigratorVersion')
				->with('calendar')
				->willReturn(2);

			$importSource->method('getFolderListing')
				->with('dav/calendars/')
				->willReturn([$calendarUri . '.meta', $calendarUri . '.data']);

			$importSource->method('getFileContents')
				->willReturnCallback(function (string $path) use ($calendarUri, $metaContent) {
					if ($path === 'dav/calendars/' . $calendarUri . '.meta') {
						return $metaContent;
					}
					throw new \Exception("Unexpected path: $path");
				});

			$importSource->method('getFileAsStream')
				->willReturnCallback(function (string $path) use ($calendarUri, $icsContent) {
					if ($path === 'dav/calendars/' . $calendarUri . '.data') {
						$stream = fopen('php://temp', 'r+');
						fwrite($stream, $icsContent);
						rewind($stream);
						return $stream;
					}
					throw new \Exception("Unexpected path: $path");
				});

			// Import the calendar
			$this->migrator->import($user, $importSource, $this->output);

			// Verify calendar was created
			$calendars = $this->getCalendarsForUser($user);
			$this->assertCount(1, $calendars, 'Expected one calendar to be created');

			// Verify the calendar URI has the migrated prefix
			$calendar = reset($calendars);
			$expectedUri = 'migrated-' . $calendarUri;
			$this->assertEquals($expectedUri, $calendar->getUri());

			// Verify calendar display name
			if ($calendarName !== null) {
				$this->assertEquals($calendarName, $calendar->getDisplayName());
			}

			// Verify calendar has objects
			$objects = $this->calDavBackend->getCalendarObjects((int)$calendar->getKey());
			$this->assertNotEmpty($objects, 'Expected calendar to have objects');
		} finally {
			$this->deleteUser($user);
		}
	}

	public function testExport(): void {
		$user = $this->createTestUser();

		try {
			// Create a calendar to export
			$principalUri = self::USERS_URI_ROOT . $user->getUID();
			$calendarUri = 'test-export-calendar';
			$calendarId = $this->calDavBackend->createCalendar($principalUri, $calendarUri, [
				'{DAV:}displayname' => 'Test Export Calendar',
				'{http://apple.com/ns/ical/}calendar-color' => '#ff0000',
			]);

			// Add an event to the calendar
			$icsContent = file_get_contents(self::ASSETS_DIR . 'event-timed.ics');
			$this->calDavBackend->createCalendarObject($calendarId, 'test-event.ics', $icsContent);

			// Setup export destination mock
			$exportDestination = $this->createMock(IExportDestination::class);

			$exportedMeta = null;
			$exportedData = null;

			$exportDestination->method('addFileContents')
				->willReturnCallback(function (string $path, string $content) use (&$exportedMeta) {
					if (str_ends_with($path, '.meta')) {
						$exportedMeta = json_decode($content, true);
					}
				});

			$exportDestination->method('addFileAsStream')
				->willReturnCallback(function (string $path, $stream) use (&$exportedData) {
					if (str_ends_with($path, '.data')) {
						$exportedData = stream_get_contents($stream);
					}
				});

			// Export the calendar
			$this->migrator->export($user, $exportDestination, $this->output);

			// Verify meta was exported
			$this->assertNotNull($exportedMeta, 'Expected meta to be exported');
			$this->assertEquals('ical', $exportedMeta['format']);
			$this->assertEquals($calendarUri, $exportedMeta['uri']);
			$this->assertEquals('Test Export Calendar', $exportedMeta['label']);
			$this->assertEquals('#ff0000', $exportedMeta['color']);

			// Verify data was exported
			$this->assertNotNull($exportedData, 'Expected data to be exported');
			$this->assertIsString($exportedData);
			/** @var string $exportedData */
			$this->assertStringContainsString('BEGIN:VCALENDAR', $exportedData);
			$this->assertStringContainsString('BEGIN:VEVENT', $exportedData);
		} finally {
			$this->deleteUser($user);
		}
	}

	public function testExportImportRoundTrip(): void {
		$user = $this->createTestUser();

		try {
			// Create a calendar with some events
			$principalUri = self::USERS_URI_ROOT . $user->getUID();
			$calendarUri = 'roundtrip-calendar';
			$calendarId = $this->calDavBackend->createCalendar($principalUri, $calendarUri, [
				'{DAV:}displayname' => 'Round Trip Calendar',
				'{http://apple.com/ns/ical/}calendar-color' => '#00ff00',
			]);

			// Add events to the calendar
			$icsContent = file_get_contents(self::ASSETS_DIR . 'event-timed.ics');
			$this->calDavBackend->createCalendarObject($calendarId, 'event1.ics', $icsContent);

			// Capture exported data
			$exportedFiles = [];

			$exportDestination = $this->createMock(IExportDestination::class);
			$exportDestination->method('addFileContents')
				->willReturnCallback(function (string $path, string $content) use (&$exportedFiles) {
					$exportedFiles[$path] = $content;
				});
			$exportDestination->method('addFileAsStream')
				->willReturnCallback(function (string $path, $stream) use (&$exportedFiles) {
					$exportedFiles[$path] = stream_get_contents($stream);
				});

			// Export
			$this->migrator->export($user, $exportDestination, $this->output);

			// Delete the original calendar
			$this->calDavBackend->deleteCalendar($calendarId, true);

			// Verify calendar is gone
			$calendars = $this->getCalendarsForUser($user);
			$this->assertEmpty($calendars, 'Calendar should be deleted');

			// Setup import source from exported data
			$importSource = $this->createMock(IImportSource::class);
			$importSource->method('getMigratorVersion')
				->with('calendar')
				->willReturn(2);

			$importSource->method('getFolderListing')
				->with('dav/calendars/')
				->willReturn(array_map(fn ($p) => basename($p), array_keys($exportedFiles)));

			$importSource->method('getFileContents')
				->willReturnCallback(function (string $path) use ($exportedFiles) {
					if (isset($exportedFiles[$path])) {
						return $exportedFiles[$path];
					}
					throw new \Exception("File not found: $path");
				});

			$importSource->method('getFileAsStream')
				->willReturnCallback(function (string $path) use ($exportedFiles) {
					if (isset($exportedFiles[$path])) {
						$stream = fopen('php://temp', 'r+');
						fwrite($stream, $exportedFiles[$path]);
						rewind($stream);
						return $stream;
					}
					throw new \Exception("File not found: $path");
				});

			// Import
			$this->migrator->import($user, $importSource, $this->output);

			// Verify calendar was recreated with migrated prefix
			$calendars = $this->getCalendarsForUser($user);
			$this->assertCount(1, $calendars, 'Expected one calendar after import');

			$calendar = reset($calendars);
			$this->assertEquals('migrated-' . $calendarUri, $calendar->getUri());
			$this->assertEquals('Round Trip Calendar', $calendar->getDisplayName());

			// Verify events were imported
			$objects = $this->calDavBackend->getCalendarObjects((int)$calendar->getKey());
			$this->assertCount(1, $objects, 'Expected one event after import');
		} finally {
			$this->deleteUser($user);
		}
	}

	public function testGetEstimatedExportSize(): void {
		$user = $this->createTestUser();

		try {
			// Initially should be 0 or minimal
			$initialSize = $this->migrator->getEstimatedExportSize($user);
			$this->assertEquals(0, $initialSize);

			// Create a calendar with events
			$principalUri = self::USERS_URI_ROOT . $user->getUID();
			$calendarUri = 'size-test-calendar';
			$calendarId = $this->calDavBackend->createCalendar($principalUri, $calendarUri, [
				'{DAV:}displayname' => 'Size Test Calendar',
			]);

			// Add an event
			$icsContent = file_get_contents(self::ASSETS_DIR . 'event-timed.ics');
			$this->calDavBackend->createCalendarObject($calendarId, 'event.ics', $icsContent);

			// Size should now be > 0
			$sizeWithData = $this->migrator->getEstimatedExportSize($user);
			$this->assertGreaterThan(0, $sizeWithData);
		} finally {
			$this->deleteUser($user);
		}
	}

	public function testImportExistingCalendarSkipped(): void {
		$user = $this->createTestUser();

		try {
			$principalUri = self::USERS_URI_ROOT . $user->getUID();

			// Pre-create a calendar with the migrated prefix
			$calendarUri = 'migrated-existing-calendar';
			$this->calDavBackend->createCalendar($principalUri, $calendarUri, [
				'{DAV:}displayname' => 'Existing Calendar',
			]);

			// Setup import for V2
			$importSource = $this->createMock(IImportSource::class);
			$importSource->method('getMigratorVersion')
				->with('calendar')
				->willReturn(2);

			$importSource->method('getFolderListing')
				->with('dav/calendars/')
				->willReturn(['existing-calendar.meta', 'existing-calendar.data']);

			$importSource->method('getFileContents')
				->willReturn(json_encode([
					'format' => 'ical',
					'uri' => 'existing-calendar',
					'label' => 'Existing Calendar',
					'color' => '#0082c9',
					'timezone' => null,
				]));

			$icsContent = file_get_contents(self::ASSETS_DIR . 'event-timed.ics');
			$importSource->method('getFileAsStream')
				->willReturnCallback(function () use ($icsContent) {
					$stream = fopen('php://temp', 'r+');
					fwrite($stream, $icsContent);
					rewind($stream);
					return $stream;
				});

			// Import should use existing calendar
			$this->migrator->import($user, $importSource, $this->output);

			// Should still have just one calendar
			$calendars = $this->getCalendarsForUser($user);
			$this->assertCount(1, $calendars);
		} finally {
			$this->deleteUser($user);
		}
	}
}
