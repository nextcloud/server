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

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
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

	public function testImportV1(): void {
		$user = $this->createTestUser();

		try {
			// Get all asset files
			$files = scandir(self::ASSETS_DIR);
			$this->assertNotFalse($files, 'Failed to scan assets directory');
			$files = array_values(array_diff($files, ['.', '..']));
			$this->assertNotEmpty($files, 'No asset files found');

			// Load all ICS content
			$icsContents = [];
			foreach ($files as $filename) {
				$icsContents[$filename] = file_get_contents(self::ASSETS_DIR . $filename);
			}

			// Setup import source mock
			$importSource = $this->createMock(IImportSource::class);
			$importSource->method('getMigratorVersion')
				->with('calendar')
				->willReturn(1);
			$importSource->method('getFolderListing')
				->with('dav/calendars/')
				->willReturn($files);
			$importSource->method('getFileAsStream')
				->willReturnCallback(function (string $path) use ($icsContents) {
					foreach ($icsContents as $filename => $content) {
						if ($path === 'dav/calendars/' . $filename) {
							$stream = fopen('php://temp', 'r+');
							fwrite($stream, $content);
							rewind($stream);
							return $stream;
						}
					}
					throw new \Exception("Unexpected path: $path");
				});

			// Import all calendars
			$this->migrator->import($user, $importSource, $this->output);

			// Verify all calendars were created
			$calendars = $this->getCalendarsForUser($user);
			$this->assertCount(count($files), $calendars, 'Expected all calendars to be created');

			// Verify each calendar has the migrated prefix and has objects
			foreach ($files as $filename) {
				$expectedUri = 'migrated-' . substr($filename, 0, -4);
				$found = false;
				foreach ($calendars as $calendar) {
					if ($calendar->getUri() === $expectedUri) {
						$found = true;
						// Verify calendar has objects
						$objects = $this->calDavBackend->getCalendarObjects((int)$calendar->getKey());
						$this->assertNotEmpty($objects, "Expected calendar $expectedUri to have objects");
						break;
					}
				}
				$this->assertTrue($found, "Calendar with URI $expectedUri was not found");
			}
		} finally {
			$this->deleteUser($user);
		}
	}

	public function testImportV2(): void {
		$user = $this->createTestUser();

		try {
			// Get all asset files
			$files = scandir(self::ASSETS_DIR);
			$this->assertNotFalse($files, 'Failed to scan assets directory');
			$files = array_values(array_diff($files, ['.', '..']));
			$this->assertNotEmpty($files, 'No asset files found');

			// Load all ICS content and build calendars metadata
			$calendarsMetadata = [];
			$icsContents = [];
			foreach ($files as $filename) {
				$icsContent = file_get_contents(self::ASSETS_DIR . $filename);
				$calendarUri = substr($filename, 0, -4);
				$icsContents[$calendarUri] = $icsContent;

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

				$calendarsMetadata[] = [
					'format' => 'ical',
					'uri' => $calendarUri,
					'label' => $calendarName ?? $calendarUri,
					'color' => $calendarColor ?? '#0082c9',
					'timezone' => null,
				];
			}

			// Setup import source mock for V2 format (.meta + .data files)
			$importSource = $this->createMock(IImportSource::class);
			$calendarsJson = json_encode($calendarsMetadata);

			$importSource->method('getMigratorVersion')
				->with('calendar')
				->willReturn(2);

			$importSource->method('pathExists')
				->willReturnCallback(function (string $path) {
					if ($path === 'dav/calendars/calendars.json') {
						return true;
					}
					if ($path === 'dav/calendars/subscriptions.json') {
						return false;
					}
					return false;
				});

			$importSource->method('getFileContents')
				->willReturnCallback(function (string $path) use ($calendarsJson) {
					if ($path === 'dav/calendars/calendars.json') {
						return $calendarsJson;
					}
					throw new \Exception("Unexpected path: $path");
				});

			$importSource->method('getFileAsStream')
				->willReturnCallback(function (string $path) use ($icsContents) {
					foreach ($icsContents as $calendarUri => $icsContent) {
						if ($path === 'dav/calendars/' . $calendarUri . '.data') {
							$stream = fopen('php://temp', 'r+');
							fwrite($stream, $icsContent);
							rewind($stream);
							return $stream;
						}
					}
					throw new \Exception("Unexpected path: $path");
				});

			// Import all calendars
			$this->migrator->import($user, $importSource, $this->output);

			// Verify all calendars were created
			$calendars = $this->getCalendarsForUser($user);
			$this->assertCount(count($files), $calendars, 'Expected all calendars to be created');

			// Verify each calendar has the correct properties and objects
			foreach ($calendarsMetadata as $metadata) {
				$expectedUri = 'migrated-' . $metadata['uri'];
				$found = false;
				foreach ($calendars as $calendar) {
					if ($calendar->getUri() === $expectedUri) {
						$found = true;
						// Verify calendar display name
						$this->assertEquals($metadata['label'], $calendar->getDisplayName());
						// Verify calendar has objects
						$objects = $this->calDavBackend->getCalendarObjects((int)$calendar->getKey());
						$this->assertNotEmpty($objects, "Expected calendar $expectedUri to have objects");
						break;
					}
				}
				$this->assertTrue($found, "Calendar with URI $expectedUri was not found");
			}
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

			$exportedCalendarsJson = null;
			$exportedData = null;

			$exportDestination->method('addFileContents')
				->willReturnCallback(function (string $path, string $content) use (&$exportedCalendarsJson) {
					if ($path === 'dav/calendars/calendars.json') {
						$exportedCalendarsJson = json_decode($content, true);
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

			// Verify calendars.json was exported
			$this->assertNotNull($exportedCalendarsJson, 'Expected calendars.json to be exported');
			$this->assertIsArray($exportedCalendarsJson);
			$this->assertCount(1, $exportedCalendarsJson);
			$exportedMeta = $exportedCalendarsJson[0];
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

			$importSource->method('pathExists')
				->willReturnCallback(function (string $path) use ($exportedFiles) {
					return isset($exportedFiles[$path]);
				});
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

			$importSource->method('pathExists')
				->willReturnCallback(function (string $path) {
					if ($path === 'dav/calendars/calendars.json') {
						return true;
					}
					if ($path === 'dav/calendars/subscriptions.json') {
						return false;
					}
					return false;
				});

			$importSource->method('getFileContents')
				->willReturnCallback(function (string $path) {
					if ($path === 'dav/calendars/calendars.json') {
						return json_encode([[
							'format' => 'ical',
							'uri' => 'existing-calendar',
							'label' => 'Existing Calendar',
							'color' => '#0082c9',
							'timezone' => null,
						]]);
					}
					throw new \Exception("Unexpected path: $path");
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

	public function testExportSubscriptions(): void {
		$user = $this->createTestUser();

		try {
			// Create a subscription to export
			$principalUri = self::USERS_URI_ROOT . $user->getUID();
			$this->calDavBackend->createSubscription(
				$principalUri,
				'test-subscription',
				[
					'{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Xml\Property\Href('https://example.com/calendar.ics'),
					'{DAV:}displayname' => 'Test Subscription',
					'{http://apple.com/ns/ical/}calendar-color' => '#ff0000',
					'{http://calendarserver.org/ns/}subscribed-strip-todos' => '1',
				]
			);

			// Setup export destination mock
			$exportDestination = $this->createMock(IExportDestination::class);

			$exportedSubscriptionsJson = null;

			$exportDestination->method('addFileContents')
				->willReturnCallback(function (string $path, string $content) use (&$exportedSubscriptionsJson) {
					if ($path === 'dav/calendars/subscriptions.json') {
						$exportedSubscriptionsJson = json_decode($content, true);
					}
				});

			$exportDestination->method('addFileAsStream');

			// Export
			$this->migrator->export($user, $exportDestination, $this->output);

			// Verify exported subscription data
			$this->assertNotNull($exportedSubscriptionsJson, 'Subscriptions JSON should be exported');
			$this->assertCount(1, $exportedSubscriptionsJson, 'Expected one subscription in export');

			$exportedSubscription = $exportedSubscriptionsJson[0];
			$this->assertEquals('test-subscription', $exportedSubscription['uri']);
			$this->assertEquals('Test Subscription', $exportedSubscription['displayname']);
			$this->assertEquals('#ff0000', $exportedSubscription['color']);
			$this->assertEquals('https://example.com/calendar.ics', $exportedSubscription['source']);
			$this->assertEquals('1', $exportedSubscription['striptodos']);
		} finally {
			$this->deleteUser($user);
		}
	}

	public function testImportSubscriptions(): void {
		$user = $this->createTestUser();

		try {
			// Setup import source mock
			$importSource = $this->createMock(IImportSource::class);

			$subscriptionsJson = json_encode([[
				'uri' => 'imported-subscription',
				'displayname' => 'Imported Subscription',
				'color' => '#00ff00',
				'source' => 'https://example.com/imported.ics',
				'striptodos' => null,
				'stripalarms' => '1',
				'stripattachments' => null,
			]]);

			$importSource->method('getMigratorVersion')
				->with('calendar')
				->willReturn(2);

			$importSource->method('pathExists')
				->willReturnCallback(function (string $path) {
					if ($path === 'dav/calendars/subscriptions.json') {
						return true;
					}
					if ($path === 'dav/calendars/calendars.json') {
						return false;
					}
					return false;
				});

			$importSource->method('getFileContents')
				->willReturnCallback(function (string $path) use ($subscriptionsJson) {
					if ($path === 'dav/calendars/subscriptions.json') {
						return $subscriptionsJson;
					}
					if ($path === 'dav/calendars/calendars.json') {
						// Return empty calendars array
						return json_encode([]);
					}
					throw new \Exception("Unexpected path: $path");
				});

			// Import
			$this->migrator->import($user, $importSource, $this->output);

			// Verify subscription was created
			$principalUri = self::USERS_URI_ROOT . $user->getUID();
			$subscriptions = $this->calDavBackend->getSubscriptionsForUser($principalUri);
			$this->assertCount(1, $subscriptions);

			$subscription = $subscriptions[0];
			$this->assertEquals('migrated-imported-subscription', $subscription['uri']);
			$this->assertEquals('Imported Subscription', $subscription['{DAV:}displayname']);
			$this->assertEquals('#00ff00', $subscription['{http://apple.com/ns/ical/}calendar-color']);
			$this->assertEquals('1', $subscription['{http://calendarserver.org/ns/}subscribed-strip-alarms']);
		} finally {
			$this->deleteUser($user);
		}
	}

	public function testExportImportSubscriptionsRoundTrip(): void {
		$user = $this->createTestUser();

		try {
			// Create subscriptions to export
			$principalUri = self::USERS_URI_ROOT . $user->getUID();
			$this->calDavBackend->createSubscription(
				$principalUri,
				'roundtrip-subscription',
				[
					'{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Xml\Property\Href('https://example.com/roundtrip.ics'),
					'{DAV:}displayname' => 'Round Trip Subscription',
					'{http://apple.com/ns/ical/}calendar-color' => '#0000ff',
				]
			);

			// Capture exported data
			$exportedFiles = [];

			$exportDestination = $this->createMock(IExportDestination::class);
			$exportDestination->method('addFileContents')
				->willReturnCallback(function (string $path, string $content) use (&$exportedFiles) {
					$exportedFiles[$path] = $content;
				});

			$exportDestination->method('addFileAsStream');

			// Export
			$this->migrator->export($user, $exportDestination, $this->output);

			// Delete the original subscription
			$subscriptions = $this->calDavBackend->getSubscriptionsForUser($principalUri);
			foreach ($subscriptions as $subscription) {
				$this->calDavBackend->deleteSubscription($subscription['id']);
			}

			// Verify subscription is gone
			$subscriptions = $this->calDavBackend->getSubscriptionsForUser($principalUri);
			$this->assertEmpty($subscriptions, 'Subscription should be deleted');

			// Setup import source from exported data
			$importSource = $this->createMock(IImportSource::class);
			$importSource->method('getMigratorVersion')
				->with('calendar')
				->willReturn(2);

			$importSource->method('pathExists')
				->willReturnCallback(function (string $path) use ($exportedFiles) {
					return isset($exportedFiles[$path]);
				});

			$importSource->method('getFileContents')
				->willReturnCallback(function (string $path) use ($exportedFiles) {
					if (isset($exportedFiles[$path])) {
						return $exportedFiles[$path];
					}
					// Return empty for missing files
					if ($path === 'dav/calendars/calendars.json') {
						return json_encode([]);
					}
					throw new \Exception("File not found: $path");
				});

			// Import
			$this->migrator->import($user, $importSource, $this->output);

			// Verify subscription was recreated with migrated prefix
			$subscriptions = $this->calDavBackend->getSubscriptionsForUser($principalUri);
			$this->assertCount(1, $subscriptions, 'Expected one subscription after import');

			$subscription = $subscriptions[0];
			$this->assertEquals('migrated-roundtrip-subscription', $subscription['uri']);
			$this->assertEquals('Round Trip Subscription', $subscription['{DAV:}displayname']);
			$this->assertEquals('#0000ff', $subscription['{http://apple.com/ns/ical/}calendar-color']);
		} finally {
			$this->deleteUser($user);
		}
	}
}
