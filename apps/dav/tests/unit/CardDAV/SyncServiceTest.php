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
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sabre\VObject\Component\VCard;
use Test\TestCase;

class SyncServiceTest extends TestCase {

	protected CardDavBackend $backend;
	protected IUserManager $userManager;
	protected IDBConnection $dbConnection;
	protected LoggerInterface $logger;
	protected Converter $converter;
	protected IClient $client;
	protected SyncService $service;
	public function setUp(): void {
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
		$this->logger = new NullLogger();
		$this->converter = $this->createMock(Converter::class);
		$this->client = $this->createMock(IClient::class);

		$clientService = $this->createMock(IClientService::class);
		$clientService->method('newClient')
			->willReturn($this->client);

		$this->service = new SyncService(
			$this->backend,
			$this->userManager,
			$this->dbConnection,
			$this->logger,
			$this->converter,
			$clientService
		);
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
			null,
			'1',
			'principals/system/system',
			[]
		);

		$this->assertEquals('http://sabre.io/ns/sync/1', $token);
	}

	public function testSyncWithNewElement(): void {
		$this->backend->expects($this->exactly(1))
			->method('createCard');
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

		$this->client
			->method('request')
			->willReturn($reportResponse);

		$vCard = 'BEGIN:VCARD
VERSION:3.0
PRODID:-//Sabre//Sabre VObject 4.5.4//EN
UID:alice
FN;X-NC-SCOPE=v2-federated:alice
N;X-NC-SCOPE=v2-federated:alice;;;;
X-SOCIALPROFILE;TYPE=NEXTCLOUD;X-NC-SCOPE=v2-published:https://server2.internal/index.php/u/alice
CLOUD:alice@server2.internal
END:VCARD';

		$getResponse = new Response(new PsrResponse(
			200,
			['Content-Type' => 'text/vcard; charset=utf-8', 'Content-Length' => strlen($vCard)],
			$vCard,
		));

		$this->client
			->method('get')
			->willReturn($getResponse);

		$token = $this->service->syncRemoteAddressBook(
			'',
			'system',
			'system',
			'1234567890',
			null,
			'1',
			'principals/system/system',
			[]
		);

		$this->assertEquals('http://sabre.io/ns/sync/2', $token);
	}

	public function testSyncWithUpdatedElement(): void {
		$this->backend->expects($this->exactly(0))
			->method('createCard');
		$this->backend->expects($this->exactly(1))
			->method('updateCard');
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

		$this->client
			->method('request')
			->willReturn($reportResponse);

		$vCard = 'BEGIN:VCARD
VERSION:3.0
PRODID:-//Sabre//Sabre VObject 4.5.4//EN
UID:alice
FN;X-NC-SCOPE=v2-federated:alice
N;X-NC-SCOPE=v2-federated:alice;;;;
X-SOCIALPROFILE;TYPE=NEXTCLOUD;X-NC-SCOPE=v2-published:https://server2.internal/index.php/u/alice
CLOUD:alice@server2.internal
END:VCARD';

		$getResponse = new Response(new PsrResponse(
			200,
			['Content-Type' => 'text/vcard; charset=utf-8', 'Content-Length' => strlen($vCard)],
			$vCard,
		));

		$this->client
			->method('get')
			->willReturn($getResponse);

		$token = $this->service->syncRemoteAddressBook(
			'',
			'system',
			'system',
			'1234567890',
			null,
			'1',
			'principals/system/system',
			[]
		);

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

		$this->client
			->method('request')
			->willReturn($reportResponse);

		$token = $this->service->syncRemoteAddressBook(
			'',
			'system',
			'system',
			'1234567890',
			null,
			'1',
			'principals/system/system',
			[]
		);

		$this->assertEquals('http://sabre.io/ns/sync/4', $token);
	}

	public function testEnsureSystemAddressBookExists(): void {
		/** @var CardDavBackend | \PHPUnit\Framework\MockObject\MockObject $backend */
		$backend = $this->getMockBuilder(CardDavBackend::class)->disableOriginalConstructor()->getMock();
		$backend->expects($this->exactly(1))->method('createAddressBook');
		$backend->expects($this->exactly(2))
			->method('getAddressBooksByUri')
			->willReturnOnConsecutiveCalls(
				null,
				[],
			);

		/** @var IUserManager $userManager */
		$userManager = $this->getMockBuilder(IUserManager::class)->disableOriginalConstructor()->getMock();
		$dbConnection = $this->createMock(IDBConnection::class);
		$logger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();
		$converter = $this->createMock(Converter::class);
		$clientService = $this->createMock(IClientService::class);

		$ss = new SyncService($backend, $userManager, $dbConnection, $logger, $converter, $clientService);
		$ss->ensureSystemAddressBookExists('principals/users/adam', 'contacts', []);
	}

	public function dataActivatedUsers() {
		return [
			[true, 1, 1, 1],
			[false, 0, 0, 3],
		];
	}

	/**
	 * @dataProvider dataActivatedUsers
	 *
	 * @param boolean $activated
	 * @param integer $createCalls
	 * @param integer $updateCalls
	 * @param integer $deleteCalls
	 * @return void
	 */
	public function testUpdateAndDeleteUser($activated, $createCalls, $updateCalls, $deleteCalls): void {
		/** @var CardDavBackend | \PHPUnit\Framework\MockObject\MockObject $backend */
		$backend = $this->getMockBuilder(CardDavBackend::class)->disableOriginalConstructor()->getMock();
		$logger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();

		$backend->expects($this->exactly($createCalls))->method('createCard');
		$backend->expects($this->exactly($updateCalls))->method('updateCard');
		$backend->expects($this->exactly($deleteCalls))->method('deleteCard');

		$backend->method('getCard')->willReturnOnConsecutiveCalls(false, [
			'carddata' => "BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.4.8//EN\r\nUID:test-user\r\nFN:test-user\r\nN:test-user;;;;\r\nEND:VCARD\r\n\r\n"
		]);

		$backend->method('getAddressBooksByUri')
			->with('principals/system/system', 'system')
			->willReturn(['id' => -1]);

		/** @var IUserManager | \PHPUnit\Framework\MockObject\MockObject $userManager */
		$userManager = $this->getMockBuilder(IUserManager::class)->disableOriginalConstructor()->getMock();
		$dbConnection = $this->createMock(IDBConnection::class);

		/** @var IUser | \PHPUnit\Framework\MockObject\MockObject $user */
		$user = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$user->method('getBackendClassName')->willReturn('unittest');
		$user->method('getUID')->willReturn('test-user');
		$user->method('getCloudId')->willReturn('cloudId');
		$user->method('getDisplayName')->willReturn('test-user');
		$user->method('isEnabled')->willReturn($activated);
		$converter = $this->createMock(Converter::class);
		$converter->expects($this->any())
			->method('createCardFromUser')
			->willReturn($this->createMock(VCard::class));

		$clientService = $this->createMock(IClientService::class);

		$ss = new SyncService($backend, $userManager, $dbConnection, $logger, $converter, $clientService);
		$ss->updateUser($user);

		$ss->updateUser($user);

		$ss->deleteUser($user);
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
}
