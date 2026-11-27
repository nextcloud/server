<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Federation;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Federation\FederatedCalendarEntity;
use OCA\DAV\CalDAV\Federation\FederatedCalendarMapper;
use OCA\DAV\CalDAV\Federation\FederatedCalendarSyncService;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class FederatedCalendarSyncServiceTest extends TestCase {
	private FederatedCalendarSyncService $federatedCalendarSyncService;

	private FederatedCalendarMapper&MockObject $federatedCalendarMapper;
	private LoggerInterface&MockObject $logger;
	private CalDavBackend&MockObject $backend;
	private ICloudIdManager&MockObject $cloudIdManager;
	private IClientService&MockObject $clientService;
	private IConfig&MockObject $config;

	protected function setUp(): void {
		parent::setUp();

		$this->federatedCalendarMapper = $this->createMock(FederatedCalendarMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->backend = $this->createMock(CalDavBackend::class);
		$this->cloudIdManager = $this->createMock(ICloudIdManager::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->config = $this->createMock(IConfig::class);

		$this->federatedCalendarSyncService = new FederatedCalendarSyncService(
			$this->clientService,
			$this->config,
			$this->federatedCalendarMapper,
			$this->logger,
			$this->backend,
			$this->cloudIdManager,
		);
	}

	public function testSyncOne(): void {
		$calendar = new FederatedCalendarEntity();
		$calendar->setId(1);
		$calendar->setPrincipaluri('principals/users/user1');
		$calendar->setRemoteUrl('https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2');
		$calendar->setSyncToken(100);
		$calendar->setToken('token');

		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getId')
			->willReturn('user1@nextcloud.testing');
		$this->cloudIdManager->expects(self::once())
			->method('getCloudId')
			->with('user1')
			->willReturn($cloudId);

		// Mock HTTP client for sync report
		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
		$response->method('getBody')
			->willReturn('<?xml version="1.0"?><d:multistatus xmlns:d="DAV:"><d:sync-token>http://sabre.io/ns/sync/101</d:sync-token></d:multistatus>');

		$client->expects(self::once())
			->method('request')
			->with('REPORT', 'https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2', self::anything())
			->willReturn($response);

		$this->clientService->method('newClient')
			->willReturn($client);

		$this->config->method('getSystemValueInt')
			->willReturn(30);
		$this->config->method('getSystemValue')
			->willReturn(false);

		$this->federatedCalendarMapper->expects(self::once())
			->method('updateSyncTokenAndTime')
			->with(1, 101);
		$this->federatedCalendarMapper->expects(self::never())
			->method('updateSyncTime');

		$this->assertEquals(0, $this->federatedCalendarSyncService->syncOne($calendar));
	}

	public function testSyncOneUnchanged(): void {
		$calendar = new FederatedCalendarEntity();
		$calendar->setId(1);
		$calendar->setPrincipaluri('principals/users/user1');
		$calendar->setRemoteUrl('https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2');
		$calendar->setSyncToken(100);
		$calendar->setToken('token');

		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getId')
			->willReturn('user1@nextcloud.testing');
		$this->cloudIdManager->expects(self::once())
			->method('getCloudId')
			->with('user1')
			->willReturn($cloudId);

		// Mock HTTP client for sync report
		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
		$response->method('getBody')
			->willReturn('<?xml version="1.0"?><d:multistatus xmlns:d="DAV:"><d:sync-token>http://sabre.io/ns/sync/100</d:sync-token></d:multistatus>');

		$client->expects(self::once())
			->method('request')
			->with('REPORT', 'https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2', self::anything())
			->willReturn($response);

		$this->clientService->method('newClient')
			->willReturn($client);

		$this->config->method('getSystemValueInt')
			->willReturn(30);
		$this->config->method('getSystemValue')
			->willReturn(false);

		$this->federatedCalendarMapper->expects(self::never())
			->method('updateSyncTokenAndTime');
		$this->federatedCalendarMapper->expects(self::once())
			->method('updateSyncTime')
			->with(1);

		$this->assertEquals(0, $this->federatedCalendarSyncService->syncOne($calendar));
	}

	public static function provideExistingObjectData(): array {
		return [
			'new object' => [null, 1, 0],
			'existing object' => [['id' => 10], 0, 1],
		];
	}

	/**
	 * @param list<string> $changedHrefs Hrefs reported as created or updated by the sync report
	 * @param array<string, string> $calendarObjects Calendar data keyed by href, as stored on the remote
	 * @return \ArrayObject Requests sent to the remote: hrefs of each multiget under 'multiGets', downloaded URLs under 'gets'
	 */
	private function mockRemote(array $changedHrefs, array $calendarObjects, bool $multiGetSupported): \ArrayObject {
		$requests = new \ArrayObject(['multiGets' => [], 'gets' => []]);

		$syncResponses = '';
		foreach ($changedHrefs as $href) {
			$syncResponses .= "<d:response><d:href>$href</d:href><d:propstat><d:prop><d:getetag>\"abc\"</d:getetag></d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response>";
		}
		$syncBody = '<?xml version="1.0"?><d:multistatus xmlns:d="DAV:">' . $syncResponses . '<d:sync-token>http://sabre.io/ns/sync/101</d:sync-token></d:multistatus>';
		$reports = $multiGetSupported
			? '<d:supported-report><d:report><d:sync-collection/></d:report></d:supported-report><d:supported-report><d:report><cal:calendar-multiget/></d:report></d:supported-report>'
			: '<d:supported-report><d:report><d:sync-collection/></d:report></d:supported-report>';
		$propFindBody = '<?xml version="1.0"?><d:multistatus xmlns:d="DAV:" xmlns:cal="urn:ietf:params:xml:ns:caldav"><d:response><d:href>/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2/</d:href><d:propstat><d:prop><d:supported-report-set>' . $reports . '</d:supported-report-set></d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response></d:multistatus>';

		$client = $this->createMock(IClient::class);
		$client->method('request')
			->willReturnCallback(function (string $method, string $uri, array $options) use ($requests, $syncBody, $propFindBody, $calendarObjects) {
				$this->assertSame('https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2', $uri);
				$response = $this->createMock(IResponse::class);
				if ($method === 'PROPFIND') {
					$this->assertSame('0', $options['headers']['Depth']);
					$response->method('getBody')->willReturn($propFindBody);
					return $response;
				}
				if (!str_contains($options['body'], 'calendar-multiget')) {
					$response->method('getBody')->willReturn($syncBody);
					return $response;
				}

				$request = new \DOMDocument();
				$request->loadXML($options['body']);
				$this->assertSame('urn:ietf:params:xml:ns:caldav', $request->documentElement->namespaceURI);
				$this->assertSame(1, $request->getElementsByTagNameNS('urn:ietf:params:xml:ns:caldav', 'calendar-data')->length);
				$hrefs = [];
				foreach ($request->getElementsByTagNameNS('DAV:', 'href') as $hrefElement) {
					$hrefs[] = $hrefElement->textContent;
				}
				$multiGets = $requests['multiGets'];
				$multiGets[] = $hrefs;
				$requests['multiGets'] = $multiGets;

				$objectResponses = '';
				foreach (array_intersect_key($calendarObjects, array_flip($hrefs)) as $href => $calendarData) {
					$objectResponses .= "<d:response><d:href>$href</d:href><d:propstat><d:prop><d:getetag>\"abc\"</d:getetag>"
						. '<cal:calendar-data>' . str_replace("\r", '&#13;', htmlspecialchars($calendarData, ENT_XML1)) . '</cal:calendar-data>'
						. '</d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response>';
				}
				$response->method('getBody')->willReturn('<?xml version="1.0"?><d:multistatus xmlns:d="DAV:" xmlns:cal="urn:ietf:params:xml:ns:caldav">' . $objectResponses . '</d:multistatus>');
				return $response;
			});
		$client->method('get')
			->willReturnCallback(function (string $uri) use ($requests, $calendarObjects) {
				$gets = $requests['gets'];
				$gets[] = $uri;
				$requests['gets'] = $gets;
				$response = $this->createMock(IResponse::class);
				$response->method('getBody')->willReturn($calendarObjects[parse_url($uri, PHP_URL_PATH)]);
				return $response;
			});

		$this->clientService->method('newClient')
			->willReturn($client);
		$this->config->method('getSystemValueInt')
			->willReturn(30);
		$this->config->method('getSystemValue')
			->willReturn(false);

		return $requests;
	}

	private function createSyncedCalendar(): FederatedCalendarEntity {
		$calendar = new FederatedCalendarEntity();
		$calendar->setId(1);
		$calendar->setPrincipaluri('principals/users/user1');
		$calendar->setRemoteUrl('https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2');
		$calendar->setSyncToken(100);
		$calendar->setToken('token');

		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getId')
			->willReturn('user1@nextcloud.testing');
		$this->cloudIdManager->method('getCloudId')
			->with('user1')
			->willReturn($cloudId);

		return $calendar;
	}

	#[DataProvider(methodName: 'provideExistingObjectData')]
	public function testSyncOneWithChangedObject(?array $existingObject, int $createCalls, int $updateCalls): void {
		$calendar = $this->createSyncedCalendar();
		$href = '/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2/event1.ics';
		$calendarData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nEND:VCALENDAR\r\n";
		$requests = $this->mockRemote([$href], [$href => $calendarData], true);

		$this->backend->expects(self::once())
			->method('getCalendarObject')
			->with(1, 'event1.ics', CalDavBackend::CALENDAR_TYPE_FEDERATED)
			->willReturn($existingObject);
		$this->backend->expects(self::exactly($createCalls))
			->method('createCalendarObject')
			->with(1, 'event1.ics', $calendarData, CalDavBackend::CALENDAR_TYPE_FEDERATED);
		$this->backend->expects(self::exactly($updateCalls))
			->method('updateCalendarObject')
			->with(1, 'event1.ics', $calendarData, CalDavBackend::CALENDAR_TYPE_FEDERATED);

		$this->federatedCalendarMapper->expects(self::once())
			->method('updateSyncTokenAndTime')
			->with(1, 101);

		$this->assertEquals(1, $this->federatedCalendarSyncService->syncOne($calendar));
		$this->assertSame([[$href]], $requests['multiGets']);
		$this->assertSame([], $requests['gets']);
	}

	public function testSyncOneFetchesChangedObjectsInBatches(): void {
		$calendar = $this->createSyncedCalendar();
		$calendarObjects = [];
		for ($i = 0; $i < 101; $i++) {
			$calendarObjects["/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2/event$i.ics"] = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nUID:event$i\r\nSUMMARY:A & <B>\r\nEND:VCALENDAR\r\n";
		}
		$hrefs = array_keys($calendarObjects);
		// The remote no longer returns the last object, e.g. because it was deleted in the meantime
		$missingHref = array_key_last($calendarObjects);
		unset($calendarObjects[$missingHref]);
		$requests = $this->mockRemote($hrefs, $calendarObjects, true);

		$createdObjects = [];
		$this->backend->method('getCalendarObject')
			->willReturn(null);
		$this->backend->expects(self::exactly(100))
			->method('createCalendarObject')
			->willReturnCallback(function (int $calendarId, string $objectUri, string $calendarData) use (&$createdObjects): void {
				$createdObjects[$objectUri] = $calendarData;
			});

		$this->assertEquals(100, $this->federatedCalendarSyncService->syncOne($calendar));
		$this->assertCount(2, $requests['multiGets']);
		$this->assertCount(100, $requests['multiGets'][0]);
		$this->assertSame([$missingHref], $requests['multiGets'][1]);
		$this->assertSame([], $requests['gets']);
		$this->assertArrayNotHasKey(basename($missingHref), $createdObjects);
		$this->assertSame($calendarObjects["/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2/event0.ics"], $createdObjects['event0.ics']);
	}

	public function testSyncOneDownloadsObjectsWhenMultiGetIsNotSupported(): void {
		$calendar = $this->createSyncedCalendar();
		$hrefs = [
			'/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2/event1.ics',
			'/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2/event2.ics',
		];
		$calendarObjects = [
			$hrefs[0] => "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nUID:event1\r\nEND:VCALENDAR\r\n",
			$hrefs[1] => "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nUID:event2\r\nEND:VCALENDAR\r\n",
		];
		$requests = $this->mockRemote($hrefs, $calendarObjects, false);

		$this->backend->method('getCalendarObject')
			->willReturn(null);
		$this->backend->expects(self::exactly(2))
			->method('createCalendarObject');

		$this->assertEquals(2, $this->federatedCalendarSyncService->syncOne($calendar));
		$this->assertSame([], $requests['multiGets']);
		$this->assertSame([
			'https://remote.tld' . $hrefs[0],
			'https://remote.tld' . $hrefs[1],
		], $requests['gets']);
	}

	public function testSyncOneWithoutChangedObjectsSkipsSupportCheck(): void {
		$calendar = $this->createSyncedCalendar();
		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
		$response->method('getBody')
			->willReturn('<?xml version="1.0"?><d:multistatus xmlns:d="DAV:"><d:response><d:href>/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2/event1.ics</d:href><d:status>HTTP/1.1 404 Not Found</d:status></d:response><d:sync-token>http://sabre.io/ns/sync/101</d:sync-token></d:multistatus>');
		$client->expects(self::once())
			->method('request')
			->with('REPORT', self::anything(), self::anything())
			->willReturn($response);
		$client->expects(self::never())
			->method('get');
		$this->clientService->method('newClient')
			->willReturn($client);
		$this->config->method('getSystemValueInt')
			->willReturn(30);
		$this->config->method('getSystemValue')
			->willReturn(false);

		$this->backend->expects(self::once())
			->method('deleteCalendarObject')
			->with(1, 'event1.ics', CalDavBackend::CALENDAR_TYPE_FEDERATED, true);

		$this->assertEquals(0, $this->federatedCalendarSyncService->syncOne($calendar));
	}

	public static function provideUnexpectedSyncTokenData(): array {
		return [
			['http://sabre.io/ns/sync/'],
			['http://sabre.io/ns/sync/foobar'],
			['http://sabre.io/ns/sync/23abc'],
			['http://nextcloud.com/ns/sync/33'],
		];
	}

	#[DataProvider(methodName: 'provideUnexpectedSyncTokenData')]
	public function testSyncOneWithUnexpectedSyncTokenFormat(string $syncToken): void {
		$calendar = new FederatedCalendarEntity();
		$calendar->setId(1);
		$calendar->setPrincipaluri('principals/users/user1');
		$calendar->setRemoteUrl('https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2');
		$calendar->setSyncToken(100);
		$calendar->setToken('token');

		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getId')
			->willReturn('user1@nextcloud.testing');
		$this->cloudIdManager->expects(self::once())
			->method('getCloudId')
			->with('user1')
			->willReturn($cloudId);

		// Mock HTTP client for sync report with unexpected token format
		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
		$response->method('getBody')
			->willReturn('<?xml version="1.0"?><d:multistatus xmlns:d="DAV:"><d:sync-token>' . $syncToken . '</d:sync-token></d:multistatus>');

		$client->expects(self::once())
			->method('request')
			->with('REPORT', 'https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2', self::anything())
			->willReturn($response);

		$this->clientService->method('newClient')
			->willReturn($client);

		$this->config->method('getSystemValueInt')
			->willReturn(30);
		$this->config->method('getSystemValue')
			->willReturn(false);

		$this->federatedCalendarMapper->expects(self::never())
			->method('updateSyncTokenAndTime');
		$this->federatedCalendarMapper->expects(self::never())
			->method('updateSyncTime');

		$this->assertEquals(0, $this->federatedCalendarSyncService->syncOne($calendar));
	}
}
