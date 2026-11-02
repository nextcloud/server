<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Tests\unit\CardDAV;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response as PsrResponse;
use OC\Http\Client\Response;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\Converter;
use OCA\DAV\CardDAV\SyncService;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sabre\VObject\Component\VCard;
use Test\TestCase;

class SyncServiceTest extends TestCase {

	protected CardDavBackend&MockObject $backend;
	protected IUserManager&MockObject $userManager;
	protected IDBConnection&MockObject $dbConnection;
	protected LoggerInterface $logger;
	protected Converter&MockObject $converter;
	protected IClient&MockObject $client;
	protected IConfig&MockObject $config;
	protected SyncService $service;
	protected int $transactionDepth = 0;
	/** @var list<array{uri: string, body: string, hrefs: list<string>}> */
	protected array $multiGetRequests = [];

	public function setUp(): void {
		parent::setUp();

		$addressBook = [
			'id' => 1,
			'uri' => 'system',
			'principaluri' => 'principals/system/system',
			'{DAV:}displayname' => 'system',
			// watch out, incomplete address book mock.
		];

		$this->backend = $this->createMock(CardDavBackend::class);
		$this->backend->method('getAddressBooksByUri')
			->with('principals/system/system', 1)
			->willReturn($addressBook);

		$this->userManager = $this->createMock(IUserManager::class);
		$this->dbConnection = $this->createMock(IDBConnection::class);
		$this->trackTransactionDepth($this->dbConnection);
		$this->logger = new NullLogger();
		$this->converter = $this->createMock(Converter::class);
		$this->client = $this->createMock(IClient::class);
		$this->config = $this->createMock(IConfig::class);

		$clientService = $this->createMock(IClientService::class);
		$clientService->method('newClient')
			->willReturn($this->client);

		$this->service = new SyncService(
			$clientService,
			$this->config,
			$this->backend,
			$this->userManager,
			$this->dbConnection,
			$this->logger,
			$this->converter,
		);
	}

	private function trackTransactionDepth(IDBConnection&MockObject $dbConnection): void {
		$dbConnection->method('beginTransaction')->willReturnCallback(function (): void {
			$this->transactionDepth++;
		});
		$dbConnection->method('commit')->willReturnCallback(function (): void {
			$this->transactionDepth--;
		});
		$dbConnection->method('rollBack')->willReturnCallback(function (): void {
			$this->transactionDepth--;
		});
	}

	private function assertNotInTransaction(): void {
		$this->assertSame(0, $this->transactionDepth, 'Expected no open transaction');
	}

	/**
	 * @param array<string, string> $vCards vCard data keyed by href, returned for addressbook-multiget reports
	 */
	private function mockSyncAndMultiGetReports(Response $syncResponse, array $vCards): void {
		$this->client->expects($this->never())
			->method('get');
		$this->client
			->method('request')
			->willReturnCallback(function (string $method, string $uri, array $options) use ($syncResponse, $vCards) {
				if ($method === 'PROPFIND') {
					$this->assertSame('0', $options['headers']['Depth']);
					return $this->createSupportedReportSetResponse(true);
				}
				if (!str_contains($options['body'], 'addressbook-multiget')) {
					return $syncResponse;
				}

				$request = new \DOMDocument();
				$request->loadXML($options['body']);
				$hrefs = [];
				foreach ($request->getElementsByTagNameNS('DAV:', 'href') as $hrefElement) {
					$hrefs[] = $hrefElement->textContent;
				}
				$this->multiGetRequests[] = ['uri' => $uri, 'body' => $options['body'], 'hrefs' => $hrefs];

				$responses = '';
				foreach (array_intersect_key($vCards, array_flip($hrefs)) as $href => $vCard) {
					$responses .= '<d:response><d:href>' . $href . '</d:href><d:propstat><d:prop>'
						. '<d:getetag>&quot;' . md5($vCard) . '&quot;</d:getetag>'
						. '<card:address-data>' . str_replace("\r", '&#13;', htmlspecialchars($vCard, ENT_XML1)) . '</card:address-data>'
						. '</d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response>';
				}
				$body = '<?xml version="1.0"?><d:multistatus xmlns:d="DAV:" xmlns:card="urn:ietf:params:xml:ns:carddav">' . $responses . '</d:multistatus>';
				return new Response(new PsrResponse(
					207,
					['Content-Type' => 'application/xml; charset=utf-8', 'Content-Length' => strlen($body)],
					$body
				));
			});
	}

	private function createSupportedReportSetResponse(bool $multiGetSupported): Response {
		$reports = '<d:supported-report><d:report><d:sync-collection/></d:report></d:supported-report>';
		if ($multiGetSupported) {
			$reports .= '<d:supported-report><d:report><card:addressbook-multiget/></d:report></d:supported-report>';
		}
		$body = '<?xml version="1.0"?><d:multistatus xmlns:d="DAV:" xmlns:card="urn:ietf:params:xml:ns:carddav"><d:response>'
			. '<d:href>/remote.php/dav/addressbooks/system/system/system/</d:href><d:propstat><d:prop>'
			. '<d:supported-report-set>' . $reports . '</d:supported-report-set>'
			. '</d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response></d:multistatus>';
		return new Response(new PsrResponse(
			207,
			['Content-Type' => 'application/xml; charset=utf-8', 'Content-Length' => strlen($body)],
			$body
		));
	}

	public function testEmptySync(): void {
		$this->backend->expects($this->exactly(0))
			->method('createCard');
		$this->backend->expects($this->exactly(0))
			->method('updateCard');
		$this->backend->expects($this->exactly(0))
			->method('deleteCard');

		$body = '<?xml version="1.0"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:oc="http://owncloud.org/ns">
    <d:sync-token>http://sabre.io/ns/sync/1</d:sync-token>
</d:multistatus>';

		$requestResponse = new Response(new PsrResponse(
			207,
			['Content-Type' => 'application/xml; charset=utf-8', 'Content-Length' => strlen($body)],
			$body
		));

		$this->client
			->method('request')
			->willReturn($requestResponse);

		$token = $this->service->syncRemoteAddressBook(
			'',
			'system',
			'system',
			'1234567890',
			'1',
			'1',
			'principals/system/system',
			[]
		)[0];

		$this->assertEquals('http://sabre.io/ns/sync/1', $token);
	}

	public function testSyncWithNewElement(): void {
		$this->backend->expects($this->exactly(1))
			->method('createCard')
			->willReturnCallback(fn () => $this->assertNotInTransaction());
		$this->backend->expects($this->exactly(0))
			->method('updateCard');
		$this->backend->expects($this->exactly(0))
			->method('deleteCard');

		$this->backend->method('getCard')
			->willReturn(false);

		$body = '<?xml version="1.0"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:oc="http://owncloud.org/ns">
    <d:response>
        <d:href>/remote.php/dav/addressbooks/system/system/system/Database:alice.vcf</d:href>
        <d:propstat>
            <d:prop>
                <d:getcontenttype>text/vcard; charset=utf-8</d:getcontenttype>
                <d:getetag>&quot;2df155fa5c2a24cd7f750353fc63f037&quot;</d:getetag>
            </d:prop>
            <d:status>HTTP/1.1 200 OK</d:status>
        </d:propstat>
    </d:response>
    <d:sync-token>http://sabre.io/ns/sync/2</d:sync-token>
</d:multistatus>';

		$reportResponse = new Response(new PsrResponse(
			207,
			['Content-Type' => 'application/xml; charset=utf-8', 'Content-Length' => strlen($body)],
			$body
		));


		$vCard = 'BEGIN:VCARD
VERSION:3.0
PRODID:-//Sabre//Sabre VObject 4.5.4//EN
UID:alice
FN;X-NC-SCOPE=v2-federated:alice
N;X-NC-SCOPE=v2-federated:alice;;;;
X-SOCIALPROFILE;TYPE=NEXTCLOUD;X-NC-SCOPE=v2-published:https://server2.internal/index.php/u/alice
CLOUD:alice@server2.internal
END:VCARD';

		$this->mockSyncAndMultiGetReports($reportResponse, [
			'/remote.php/dav/addressbooks/system/system/system/Database:alice.vcf' => $vCard,
		]);

		$token = $this->service->syncRemoteAddressBook(
			'',
			'system',
			'system',
			'1234567890',
			'1',
			'1',
			'principals/system/system',
			[]
		)[0];

		$this->assertEquals('http://sabre.io/ns/sync/2', $token);
	}

	public function testSyncFetchesChangedCardsWithBatchedMultiGet(): void {
		$responses = '';
		$vCards = [];
		for ($i = 0; $i < 101; $i++) {
			$href = "/remote.php/dav/addressbooks/system/system/system/Database:user$i.vcf";
			$responses .= "<d:response><d:href>$href</d:href><d:propstat><d:prop><d:getetag>&quot;$i&quot;</d:getetag></d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response>";
			$vCards[$href] = "BEGIN:VCARD\r\nVERSION:3.0\r\nUID:user$i\r\nFN:User & <$i>\r\nEND:VCARD\r\n";
		}
		// The remote no longer returns the last card, e.g. because it was deleted in the meantime
		$missingHref = array_key_last($vCards);
		unset($vCards[$missingHref]);

		$body = '<?xml version="1.0"?><d:multistatus xmlns:d="DAV:">' . $responses . '<d:sync-token>http://sabre.io/ns/sync/5</d:sync-token></d:multistatus>';
		$reportResponse = new Response(new PsrResponse(
			207,
			['Content-Type' => 'application/xml; charset=utf-8', 'Content-Length' => strlen($body)],
			$body
		));
		$this->mockSyncAndMultiGetReports($reportResponse, $vCards);

		$this->backend->method('getCard')
			->willReturn(false);
		$createdCards = [];
		$this->backend->expects($this->exactly(100))
			->method('createCard')
			->willReturnCallback(function (int $addressBookId, string $cardUri, string $cardData) use (&$createdCards): void {
				$createdCards[$cardUri] = $cardData;
			});
		$this->backend->expects($this->never())
			->method('updateCard');
		$this->backend->expects($this->never())
			->method('deleteCard');

		$token = $this->service->syncRemoteAddressBook(
			'https://server2.internal',
			'system',
			'remote.php/dav/addressbooks/system/system/system',
			'1234567890',
			'1',
			'1',
			'principals/system/system',
			[]
		)[0];

		$this->assertEquals('http://sabre.io/ns/sync/5', $token);
		$this->assertCount(2, $this->multiGetRequests);
		$this->assertCount(100, $this->multiGetRequests[0]['hrefs']);
		$this->assertSame([$missingHref], $this->multiGetRequests[1]['hrefs']);
		foreach ($this->multiGetRequests as $request) {
			$this->assertSame('https://server2.internal/remote.php/dav/addressbooks/system/system/system', $request['uri']);
			$document = new \DOMDocument();
			$document->loadXML($request['body']);
			$this->assertSame('urn:ietf:params:xml:ns:carddav', $document->documentElement->namespaceURI);
			$this->assertSame('addressbook-multiget', $document->documentElement->localName);
			$this->assertSame(1, $document->getElementsByTagNameNS('urn:ietf:params:xml:ns:carddav', 'address-data')->length);
		}
		$this->assertArrayNotHasKey(basename($missingHref), $createdCards);
		$this->assertSame($vCards['/remote.php/dav/addressbooks/system/system/system/Database:user0.vcf'], $createdCards['Database:user0.vcf']);
	}

	public function testSyncDownloadsCardsWhenMultiGetIsNotSupported(): void {
		$href = '/remote.php/dav/addressbooks/system/system/system/Database:alice.vcf';
		$body = '<?xml version="1.0"?><d:multistatus xmlns:d="DAV:"><d:response><d:href>' . $href . '</d:href><d:propstat><d:prop><d:getetag>&quot;1&quot;</d:getetag></d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response><d:sync-token>http://sabre.io/ns/sync/2</d:sync-token></d:multistatus>';
		$reportResponse = new Response(new PsrResponse(
			207,
			['Content-Type' => 'application/xml; charset=utf-8', 'Content-Length' => strlen($body)],
			$body
		));
		$vCard = "BEGIN:VCARD\r\nVERSION:3.0\r\nUID:alice\r\nFN:alice\r\nEND:VCARD\r\n";

		$requestedMethods = [];
		$this->client
			->method('request')
			->willReturnCallback(function (string $method) use ($reportResponse, &$requestedMethods) {
				$requestedMethods[] = $method;
				return $method === 'PROPFIND' ? $this->createSupportedReportSetResponse(false) : $reportResponse;
			});
		$this->client->expects($this->once())
			->method('get')
			->with('https://server2.internal' . $href)
			->willReturn(new Response(new PsrResponse(
				200,
				['Content-Type' => 'text/vcard; charset=utf-8', 'Content-Length' => strlen($vCard)],
				$vCard,
			)));

		$this->backend->method('getCard')
			->willReturn(false);
		$this->backend->expects($this->once())
			->method('createCard')
			->with(1, 'Database:alice.vcf', $vCard);

		$this->service->syncRemoteAddressBook(
			'https://server2.internal',
			'system',
			'remote.php/dav/addressbooks/system/system/system',
			'1234567890',
			'1',
			'1',
			'principals/system/system',
			[]
		);

		$this->assertSame(['REPORT', 'PROPFIND'], $requestedMethods);
	}

	public function testSyncWithUpdatedElement(): void {
		$this->backend->expects($this->exactly(0))
			->method('createCard');
		$this->backend->expects($this->exactly(1))
			->method('updateCard')
			->willReturnCallback(fn () => $this->assertNotInTransaction());
		$this->backend->expects($this->exactly(0))
			->method('deleteCard');

		$this->backend->method('getCard')
			->willReturn(true);

		$body = '<?xml version="1.0"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:oc="http://owncloud.org/ns">
    <d:response>
        <d:href>/remote.php/dav/addressbooks/system/system/system/Database:alice.vcf</d:href>
        <d:propstat>
            <d:prop>
                <d:getcontenttype>text/vcard; charset=utf-8</d:getcontenttype>
                <d:getetag>&quot;2df155fa5c2a24cd7f750353fc63f037&quot;</d:getetag>
            </d:prop>
            <d:status>HTTP/1.1 200 OK</d:status>
        </d:propstat>
    </d:response>
    <d:sync-token>http://sabre.io/ns/sync/3</d:sync-token>
</d:multistatus>';

		$reportResponse = new Response(new PsrResponse(
			207,
			['Content-Type' => 'application/xml; charset=utf-8', 'Content-Length' => strlen($body)],
			$body
		));


		$vCard = 'BEGIN:VCARD
VERSION:3.0
PRODID:-//Sabre//Sabre VObject 4.5.4//EN
UID:alice
FN;X-NC-SCOPE=v2-federated:alice
N;X-NC-SCOPE=v2-federated:alice;;;;
X-SOCIALPROFILE;TYPE=NEXTCLOUD;X-NC-SCOPE=v2-published:https://server2.internal/index.php/u/alice
CLOUD:alice@server2.internal
END:VCARD';

		$this->mockSyncAndMultiGetReports($reportResponse, [
			'/remote.php/dav/addressbooks/system/system/system/Database:alice.vcf' => $vCard,
		]);

		$token = $this->service->syncRemoteAddressBook(
			'',
			'system',
			'system',
			'1234567890',
			'1',
			'1',
			'principals/system/system',
			[]
		)[0];

		$this->assertEquals('http://sabre.io/ns/sync/3', $token);
	}

	public function testSyncWithDeletedElement(): void {
		$this->backend->expects($this->exactly(0))
			->method('createCard');
		$this->backend->expects($this->exactly(0))
			->method('updateCard');
		$this->backend->expects($this->exactly(1))
			->method('deleteCard');

		$body = '<?xml version="1.0"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:oc="http://owncloud.org/ns">
<d:response>
    <d:href>/remote.php/dav/addressbooks/system/system/system/Database:alice.vcf</d:href>
    <d:status>HTTP/1.1 404 Not Found</d:status>
</d:response>
<d:sync-token>http://sabre.io/ns/sync/4</d:sync-token>
</d:multistatus>';

		$reportResponse = new Response(new PsrResponse(
			207,
			['Content-Type' => 'application/xml; charset=utf-8', 'Content-Length' => strlen($body)],
			$body
		));

		$this->client->expects($this->once())
			->method('request')
			->with('REPORT')
			->willReturn($reportResponse);

		$token = $this->service->syncRemoteAddressBook(
			'',
			'system',
			'system',
			'1234567890',
			'1',
			'1',
			'principals/system/system',
			[]
		)[0];

		$this->assertEquals('http://sabre.io/ns/sync/4', $token);
	}

	public function testFullSyncWithOrphanElement(): void {
		$pendingCards = [];
		$this->backend->expects($this->exactly(0))
			->method('createCard');
		$this->backend->expects($this->exactly(1))
			->method('updateCard')
			->willReturnCallback(function ($id, $uri) use (&$pendingCards): void {
				unset($pendingCards[$uri]);
			});
		$this->backend->expects($this->exactly(1))
			->method('markCardsAsPending')
			->willReturnCallback(function ($id) use (&$pendingCards): void {
				$cards = array_values($this->backend->getCards($id));
				$uris = array_map(fn ($card) => $card['uri'], $cards);
				$pendingCards = array_combine($uris, $cards);
			});
		$this->backend->expects($this->exactly(1))
			->method('getPendingCards')
			->willReturnCallback(function ($id) use (&$pendingCards) {
				return array_values($pendingCards);
			});
		$this->backend->expects($this->exactly(1))
			->method('deleteCard');

		$body = '<?xml version="1.0"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:oc="http://owncloud.org/ns">
    <d:response>
        <d:href>/remote.php/dav/addressbooks/system/system/system/Database:alice.vcf</d:href>
        <d:propstat>
            <d:prop>
                <d:getcontenttype>text/vcard; charset=utf-8</d:getcontenttype>
                <d:getetag>&quot;2df155fa5c2a24cd7f750353fc63f037&quot;</d:getetag>
            </d:prop>
            <d:status>HTTP/1.1 200 OK</d:status>
        </d:propstat>
    </d:response>
    <d:sync-token>http://sabre.io/ns/sync/3</d:sync-token>
</d:multistatus>';

		$reportResponse = new Response(new PsrResponse(
			207,
			['Content-Type' => 'application/xml; charset=utf-8', 'Content-Length' => strlen($body)],
			$body
		));


		$vCard = 'BEGIN:VCARD
VERSION:3.0
PRODID:-//Sabre//Sabre VObject 4.5.4//EN
UID:alice
FN;X-NC-SCOPE=v2-federated:alice
N;X-NC-SCOPE=v2-federated:alice;;;;
X-SOCIALPROFILE;TYPE=NEXTCLOUD;X-NC-SCOPE=v2-published:https://server2.internal/index.php/u/alice
CLOUD:alice@server2.internal
END:VCARD';

		$this->mockSyncAndMultiGetReports($reportResponse, [
			'/remote.php/dav/addressbooks/system/system/system/Database:alice.vcf' => $vCard,
		]);

		$this->backend->method('getCards')
			->willReturn([
				['uri' => 'Database:alice.vcf'],
				['uri' => 'Database:bob.vcf'],
			]);

		$this->service->markCardsAsPending(1);
		$token = $this->service->syncRemoteAddressBook(
			'',
			'system',
			'system',
			'1234567890',
			null,
			'1',
			'principals/system/system',
			[]
		)[0];
		$this->service->deletePendingCards(1);

		$this->assertEquals('http://sabre.io/ns/sync/3', $token);
	}

	public function testEnsureSystemAddressBookExists(): void {
		/** @var CardDavBackend&MockObject $backend */
		$backend = $this->createMock(CardDavBackend::class);
		$backend->expects($this->exactly(1))->method('createAddressBook');
		$backend->expects($this->exactly(2))
			->method('getAddressBooksByUri')
			->willReturnOnConsecutiveCalls(
				null,
				[],
			);

		$userManager = $this->createMock(IUserManager::class);
		$dbConnection = $this->createMock(IDBConnection::class);
		$logger = $this->createMock(LoggerInterface::class);
		$converter = $this->createMock(Converter::class);
		$clientService = $this->createMock(IClientService::class);
		$config = $this->createMock(IConfig::class);

		$ss = new SyncService($clientService, $config, $backend, $userManager, $dbConnection, $logger, $converter);
		$ss->ensureSystemAddressBookExists('principals/users/adam', 'contacts', []);
	}

	public static function dataActivatedUsers(): array {
		return [
			[true, 1, 1, 1],
			[false, 0, 0, 3],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataActivatedUsers')]
	public function testUpdateAndDeleteUser(bool $activated, int $createCalls, int $updateCalls, int $deleteCalls): void {
		/** @var CardDavBackend | MockObject $backend */
		$backend = $this->getMockBuilder(CardDavBackend::class)->disableOriginalConstructor()->getMock();
		$logger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();

		$backend->expects($this->exactly($createCalls))->method('createCard')
			->willReturnCallback(fn () => $this->assertNotInTransaction());
		$backend->expects($this->exactly($updateCalls))->method('updateCard')
			->willReturnCallback(fn () => $this->assertNotInTransaction());
		$backend->expects($this->exactly($deleteCalls))->method('deleteCard');

		$backend->method('getCard')->willReturnOnConsecutiveCalls(false, [
			'carddata' => "BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.4.8//EN\r\nUID:test-user\r\nFN:test-user\r\nN:test-user;;;;\r\nEND:VCARD\r\n\r\n"
		]);

		$backend->method('getAddressBooksByUri')
			->with('principals/system/system', 'system')
			->willReturn(['id' => -1]);

		$userManager = $this->createMock(IUserManager::class);
		$dbConnection = $this->createMock(IDBConnection::class);
		$this->trackTransactionDepth($dbConnection);
		$user = $this->createMock(IUser::class);
		$user->method('getBackendClassName')->willReturn('unittest');
		$user->method('getUID')->willReturn('test-user');
		$user->method('getCloudId')->willReturn('cloudId');
		$user->method('getDisplayName')->willReturn('test-user');
		$user->method('isEnabled')->willReturn($activated);
		$converter = $this->createMock(Converter::class);
		$converter->expects($this->any())
			->method('createCardFromUser')
			->willReturnCallback(function () {
				$this->assertNotInTransaction();
				return $this->createMock(VCard::class);
			});

		$clientService = $this->createMock(IClientService::class);
		$config = $this->createMock(IConfig::class);

		$ss = new SyncService($clientService, $config, $backend, $userManager, $dbConnection, $logger, $converter);
		$ss->updateUser($user);

		$ss->updateUser($user);

		$ss->deleteUser($user);
	}

	public function testUpdateUserSkipsUnchangedCard(): void {
		$vCard = new VCard();
		$vCard->VERSION = '3.0';
		$vCard->UID = 'test-user';
		$vCard->FN = 'test-user';

		/** @var CardDavBackend&MockObject $backend */
		$backend = $this->getMockBuilder(CardDavBackend::class)->disableOriginalConstructor()->getMock();
		$logger = $this->createMock(LoggerInterface::class);

		$backend->expects($this->never())->method('createCard');
		$backend->expects($this->never())->method('updateCard');
		$backend->expects($this->never())->method('deleteCard');

		$backend->method('getCard')->willReturn(['carddata' => $vCard->serialize()]);
		$backend->method('getAddressBooksByUri')
			->with('principals/system/system', 'system')
			->willReturn(['id' => -1]);

		$user = $this->createMock(IUser::class);
		$user->method('getBackendClassName')->willReturn('unittest');
		$user->method('getUID')->willReturn('test-user');
		$user->method('isEnabled')->willReturn(true);

		$converter = $this->createMock(Converter::class);
		$converter->method('createCardFromUser')->willReturn($vCard);

		$ss = new SyncService(
			$this->createMock(IClientService::class),
			$this->createMock(IConfig::class),
			$backend,
			$this->createMock(IUserManager::class),
			$this->createMock(IDBConnection::class),
			$logger,
			$converter,
		);
		$ss->updateUser($user);
	}

	public function testSyncInstance(): void {
		/** @var CardDavBackend | MockObject $backend */
		$backend = $this->getMockBuilder(CardDavBackend::class)->disableOriginalConstructor()->getMock();
		$logger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();

		$backend->expects($this->exactly(1))->method('deleteCard');

		$backend->method('getCards')->willReturn([
			[
				'carddata' => "BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.4.8//EN\r\nUID:test-user\r\nFN:test-user\r\nN:test-user;;;;\r\nEND:VCARD\r\n\r\n",
				'uri' => 'Database:test-user.vcf',
			],
			[
				'carddata' => "BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.4.8//EN\r\nUID:test-user\r\nFN:test-user\r\nN:test-user;;;;\r\nEND:VCARD\r\n\r\n",
				'uri' => 'LDAP:test-user.vcf',
			],
		]
		);

		$backend->method('getAddressBooksByUri')
			->with('principals/system/system', 'system')
			->willReturn(['id' => -1]);

		$userManager = $this->createMock(IUserManager::class);
		$dbConnection = $this->createMock(IDBConnection::class);
		$user = $this->createMock(IUser::class);
		$user->method('getBackendClassName')->willReturn('LDAP');
		$user->method('getUID')->willReturn('test-user');
		$userManager->method('get')->willReturn($user);

		$converter = $this->createMock(Converter::class);
		$clientService = $this->createMock(IClientService::class);
		$config = $this->createMock(IConfig::class);

		$ss = new SyncService($clientService, $config, $backend, $userManager, $dbConnection, $logger, $converter);
		$ss->syncInstance();
	}

	public function testDeleteAddressbookWhenAccessRevoked(): void {
		$this->expectException(ClientExceptionInterface::class);

		$this->backend->expects($this->exactly(0))
			->method('createCard');
		$this->backend->expects($this->exactly(0))
			->method('updateCard');
		$this->backend->expects($this->exactly(0))
			->method('deleteCard');
		$this->backend->expects($this->exactly(1))
			->method('deleteAddressBook');

		$request = new PsrRequest(
			'REPORT',
			'https://server2.internal/remote.php/dav/addressbooks/system/system/system',
			['Content-Type' => 'application/xml'],
		);

		$body = '<?xml version="1.0" encoding="utf-8"?>
<d:error xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
  <s:exception>Sabre\DAV\Exception\NotAuthenticated</s:exception>
  <s:message>No public access to this resource., Username or password was incorrect, No \'Authorization: Bearer\' header found. Either the client didn\'t send one, or the server is mis-configured, Username or password was incorrect</s:message>
</d:error>';

		$response = new PsrResponse(
			401,
			['Content-Type' => 'application/xml; charset=utf-8', 'Content-Length' => strlen($body)],
			$body
		);

		$message = 'Client error: `REPORT https://server2.internal/cloud/remote.php/dav/addressbooks/system/system/system` resulted in a `401 Unauthorized` response:
<?xml version="1.0" encoding="utf-8"?>
<d:error xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
  <s:exception>Sabre\DA (truncated...)
';

		$reportException = new ClientException(
			$message,
			$request,
			$response
		);

		$this->client
			->method('request')
			->willThrowException($reportException);

		$this->service->syncRemoteAddressBook(
			'',
			'system',
			'system',
			'1234567890',
			null,
			'1',
			'principals/system/system',
			[]
		);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'providerUseAbsoluteUriReport')]
	public function testUseAbsoluteUriReport(string $host, string $expected): void {
		$body = '<?xml version="1.0"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:oc="http://owncloud.org/ns">
    <d:sync-token>http://sabre.io/ns/sync/1</d:sync-token>
</d:multistatus>';

		$requestResponse = new Response(new PsrResponse(
			207,
			['Content-Type' => 'application/xml; charset=utf-8', 'Content-Length' => strlen($body)],
			$body
		));

		$this->client
			->method('request')
			->with(
				'REPORT',
				$this->callback(function ($uri) use ($expected) {
					$this->assertEquals($expected, $uri);
					return true;
				}),
				$this->callback(function ($options) {
					$this->assertIsArray($options);
					return true;
				}),
			)
			->willReturn($requestResponse);

		$this->service->syncRemoteAddressBook(
			$host,
			'system',
			'remote.php/dav/addressbooks/system/system/system',
			'1234567890',
			'1',
			'1',
			'principals/system/system',
			[]
		);
	}

	public static function providerUseAbsoluteUriReport(): array {
		return [
			['https://server.internal', 'https://server.internal/remote.php/dav/addressbooks/system/system/system'],
			['https://server.internal/', 'https://server.internal/remote.php/dav/addressbooks/system/system/system'],
			['https://server.internal/nextcloud', 'https://server.internal/nextcloud/remote.php/dav/addressbooks/system/system/system'],
			['https://server.internal/nextcloud/', 'https://server.internal/nextcloud/remote.php/dav/addressbooks/system/system/system'],
			['https://server.internal:8080', 'https://server.internal:8080/remote.php/dav/addressbooks/system/system/system'],
			['https://server.internal:8080/', 'https://server.internal:8080/remote.php/dav/addressbooks/system/system/system'],
			['https://server.internal:8080/nextcloud', 'https://server.internal:8080/nextcloud/remote.php/dav/addressbooks/system/system/system'],
			['https://server.internal:8080/nextcloud/', 'https://server.internal:8080/nextcloud/remote.php/dav/addressbooks/system/system/system'],
		];
	}
}
