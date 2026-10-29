<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Tests\unit\CardDAV;

use OC\KnownUser\KnownUserService;
use OCA\DAV\CalDAV\Federation\FederationSharingService;
use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCA\DAV\CardDAV\AddressBook;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\Sharing\Backend;
use OCA\DAV\CardDAV\Sharing\Service;
use OCA\DAV\CardDAV\SystemAddressbook;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\RemoteUserPrincipalBackend;
use OCA\DAV\DAV\Sharing\SharingMapper;
use OCA\DAV\Exception\UidConflict;
use OCP\Accounts\IAccountManager;
use OCP\App\IAppManager;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Share\IManager as ShareManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\PropPatch;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Property\Text;
use Test\TestCase;
use function time;

/**
 * Class CardDavBackendTest
 *
 *
 * @package OCA\DAV\Tests\unit\CardDAV
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class CardDavBackendTest extends TestCase {
	private Principal&MockObject $principal;
	private IUserManager&MockObject $userManager;
	private IGroupManager&MockObject $groupManager;
	private IEventDispatcher&MockObject $dispatcher;
	private IConfig&MockObject $config;
	private RemoteUserPrincipalBackend&MockObject $remoteUserPrincipalBackend;
	private FederationSharingService&MockObject $federationSharingService;
	private Backend $sharingBackend;
	private IDBConnection $db;
	private CardDavBackend $backend;
	private string $dbCardsTable = 'cards';
	private string $dbCardsPropertiesTable = 'cards_properties';

	public const UNIT_TEST_USER = 'principals/users/carddav-unit-test';
	public const UNIT_TEST_USER1 = 'principals/users/carddav-unit-test1';
	public const UNIT_TEST_GROUP = 'principals/groups/carddav-unit-test-group';

	private $vcardTest0 = 'BEGIN:VCARD' . PHP_EOL
		. 'VERSION:3.0' . PHP_EOL
		. 'PRODID:-//Sabre//Sabre VObject 4.1.2//EN' . PHP_EOL
		. 'UID:Test' . PHP_EOL
		. 'FN:Test' . PHP_EOL
		. 'N:Test;;;;' . PHP_EOL
		. 'END:VCARD';

	private $vcardTest1 = 'BEGIN:VCARD' . PHP_EOL
		. 'VERSION:3.0' . PHP_EOL
		. 'PRODID:-//Sabre//Sabre VObject 4.1.2//EN' . PHP_EOL
		. 'UID:Test2' . PHP_EOL
		. 'FN:Test2' . PHP_EOL
		. 'N:Test2;;;;' . PHP_EOL
		. 'END:VCARD';

	private $vcardTest2 = 'BEGIN:VCARD' . PHP_EOL
		. 'VERSION:3.0' . PHP_EOL
		. 'PRODID:-//Sabre//Sabre VObject 4.1.2//EN' . PHP_EOL
		. 'UID:Test3' . PHP_EOL
		. 'FN:Test3' . PHP_EOL
		. 'N:Test3;;;;' . PHP_EOL
		. 'END:VCARD';

	private $vcardTestNoUID = 'BEGIN:VCARD' . PHP_EOL
		. 'VERSION:3.0' . PHP_EOL
		. 'PRODID:-//Sabre//Sabre VObject 4.1.2//EN' . PHP_EOL
		. 'FN:TestNoUID' . PHP_EOL
		. 'N:TestNoUID;;;;' . PHP_EOL
		. 'END:VCARD';

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->principal = $this->getMockBuilder(Principal::class)
			->setConstructorArgs([
				$this->userManager,
				$this->groupManager,
				$this->createMock(IAccountManager::class),
				$this->createMock(ShareManager::class),
				$this->createMock(IUserSession::class),
				$this->createMock(IAppManager::class),
				$this->createMock(ProxyMapper::class),
				$this->createMock(KnownUserService::class),
				$this->config,
				$this->createMock(IFactory::class)
			])
			->onlyMethods(['getPrincipalByPath', 'getGroupMembership', 'findByUri'])
			->getMock();
		$this->principal->method('getPrincipalByPath')
			->willReturn([
				'uri' => 'principals/best-friend',
				'{DAV:}displayname' => 'User\'s displayname',
			]);
		$this->principal->method('getGroupMembership')
			->withAnyParameters()
			->willReturn([self::UNIT_TEST_GROUP]);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->remoteUserPrincipalBackend = $this->createMock(RemoteUserPrincipalBackend::class);
		$this->federationSharingService = $this->createMock(FederationSharingService::class);

		$this->db = Server::get(IDBConnection::class);
		$this->sharingBackend = new Backend($this->userManager,
			$this->groupManager,
			$this->principal,
			$this->remoteUserPrincipalBackend,
			$this->createMock(ICacheFactory::class),
			new Service(new SharingMapper($this->db)),
			$this->federationSharingService,
			$this->createMock(LoggerInterface::class)
		);

		$this->backend = $this->createBackend();
		// start every test with a empty cards_properties and cards table
		$query = $this->db->getQueryBuilder();
		$query->delete('cards_properties')->executeStatement();
		$query = $this->db->getQueryBuilder();
		$query->delete('cards')->executeStatement();

		$this->principal->method('getGroupMembership')
			->withAnyParameters()
			->willReturn([self::UNIT_TEST_GROUP]);
		$books = $this->backend->getAddressBooksForUser(self::UNIT_TEST_USER);
		foreach ($books as $book) {
			$this->backend->deleteAddressBook($book['id']);
		}
	}

	/**
	 * A fresh instance starts with an empty in-memory etag cache, like a
	 * subsequent request or background job would.
	 */
	private function createBackend(): CardDavBackend {
		return new CardDavBackend($this->db,
			$this->principal,
			$this->userManager,
			$this->dispatcher,
			$this->sharingBackend,
			$this->config,
		);
	}

	private function countChanges(int $addressBookId): int {
		$query = $this->db->getQueryBuilder();
		$query->select($query->func()->count('*'))
			->from('addressbookchanges')
			->where($query->expr()->eq('addressbookid', $query->createNamedParameter($addressBookId)));
		$result = $query->executeQuery();
		$count = (int)$result->fetchOne();
		$result->closeCursor();

		return $count;
	}

	private function getSyncToken(int $addressBookId): int {
		$query = $this->db->getQueryBuilder();
		$query->select('synctoken')
			->from('addressbooks')
			->where($query->expr()->eq('id', $query->createNamedParameter($addressBookId)));
		$result = $query->executeQuery();
		$syncToken = (int)$result->fetchOne();
		$result->closeCursor();

		return $syncToken;
	}

	protected function tearDown(): void {
		if (is_null($this->backend)) {
			return;
		}

		$this->principal->method('getGroupMembership')
			->withAnyParameters()
			->willReturn([self::UNIT_TEST_GROUP]);
		$books = $this->backend->getAddressBooksForUser(self::UNIT_TEST_USER);
		foreach ($books as $book) {
			$this->backend->deleteAddressBook($book['id']);
		}

		parent::tearDown();
	}

	public function testAddressBookOperations(): void {
		// create a new address book
		$this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);

		$this->assertEquals(1, $this->backend->getAddressBooksForUserCount(self::UNIT_TEST_USER));
		$books = $this->backend->getAddressBooksForUser(self::UNIT_TEST_USER);
		$this->assertEquals(1, count($books));
		$this->assertEquals('Example', $books[0]['{DAV:}displayname']);
		$this->assertEquals('User\'s displayname', $books[0]['{http://nextcloud.com/ns}owner-displayname']);

		// update its display name
		$patch = new PropPatch([
			'{DAV:}displayname' => 'Unit test',
			'{urn:ietf:params:xml:ns:carddav}addressbook-description' => 'Addressbook used for unit testing'
		]);
		$this->backend->updateAddressBook($books[0]['id'], $patch);
		$patch->commit();
		$books = $this->backend->getAddressBooksForUser(self::UNIT_TEST_USER);
		$this->assertEquals(1, count($books));
		$this->assertEquals('Unit test', $books[0]['{DAV:}displayname']);
		$this->assertEquals('Addressbook used for unit testing', $books[0]['{urn:ietf:params:xml:ns:carddav}addressbook-description']);

		// delete the address book
		$this->backend->deleteAddressBook($books[0]['id']);
		$books = $this->backend->getAddressBooksForUser(self::UNIT_TEST_USER);
		$this->assertEquals(0, count($books));
	}

	public function testAddressBookSharing(): void {
		$this->userManager->expects($this->any())
			->method('userExists')
			->willReturn(true);
		$this->groupManager->expects($this->any())
			->method('groupExists')
			->willReturn(true);
		$this->principal->expects(self::atLeastOnce())
			->method('findByUri')
			->willReturnOnConsecutiveCalls(self::UNIT_TEST_USER1, self::UNIT_TEST_GROUP);

		$this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$books = $this->backend->getAddressBooksForUser(self::UNIT_TEST_USER);
		$this->assertEquals(1, count($books));
		$l = $this->createMock(IL10N::class);
		$addressBook = new AddressBook($this->backend, $books[0], $l);
		$this->backend->updateShares($addressBook, [
			[
				'href' => 'principal:' . self::UNIT_TEST_USER1,
			],
			[
				'href' => 'principal:' . self::UNIT_TEST_GROUP,
			]
		], []);
		$books = $this->backend->getAddressBooksForUser(self::UNIT_TEST_USER1);
		$this->assertEquals(1, count($books));

		// delete the address book
		$this->backend->deleteAddressBook($books[0]['id']);
		$books = $this->backend->getAddressBooksForUser(self::UNIT_TEST_USER);
		$this->assertEquals(0, count($books));
	}

	public function testCardOperations(): void {
		/** @var CardDavBackend&MockObject $backend */
		$backend = $this->getMockBuilder(CardDavBackend::class)
			->setConstructorArgs([$this->db, $this->principal, $this->userManager, $this->dispatcher, $this->sharingBackend,$this->config])
			->onlyMethods(['updateProperties', 'purgeProperties'])
			->getMock();

		// create a new address book
		$backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$books = $backend->getAddressBooksForUser(self::UNIT_TEST_USER);
		$this->assertEquals(1, count($books));
		$bookId = $books[0]['id'];

		$uri = $this->getUniqueID('card');
		// updateProperties is expected twice, once for createCard and once for updateCard
		$calls = [
			[$bookId, $uri, $this->vcardTest0],
			[$bookId, $uri, $this->vcardTest1],
		];
		$backend->expects($this->exactly(count($calls)))
			->method('updateProperties')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});

		// Expect event
		$this->dispatcher
			->expects($this->exactly(3))
			->method('dispatchTyped');

		// create a card
		$backend->createCard($bookId, $uri, $this->vcardTest0);

		// get all the cards
		$cards = $backend->getCards($bookId);
		$this->assertEquals(1, count($cards));
		$this->assertEquals($this->vcardTest0, $cards[0]['carddata']);

		// get the cards
		$card = $backend->getCard($bookId, $uri);
		$this->assertNotNull($card);
		$this->assertArrayHasKey('id', $card);
		$this->assertArrayHasKey('uri', $card);
		$this->assertArrayHasKey('lastmodified', $card);
		$this->assertArrayHasKey('etag', $card);
		$this->assertArrayHasKey('size', $card);
		$this->assertEquals($this->vcardTest0, $card['carddata']);

		// update the card
		$backend->updateCard($bookId, $uri, $this->vcardTest1);
		$card = $backend->getCard($bookId, $uri);
		$this->assertEquals($this->vcardTest1, $card['carddata']);

		// delete the card
		$backend->expects($this->once())->method('purgeProperties')->with($bookId, $card['id']);
		$backend->deleteCard($bookId, $uri);
		$cards = $backend->getCards($bookId);
		$this->assertEquals(0, count($cards));
	}

	public function testMultiCard(): void {
		$this->backend = $this->getMockBuilder(CardDavBackend::class)
			->setConstructorArgs([$this->db, $this->principal, $this->userManager, $this->dispatcher, $this->sharingBackend,$this->config])
			->onlyMethods(['updateProperties'])
			->getMock();

		// create a new address book
		$this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$books = $this->backend->getAddressBooksForUser(self::UNIT_TEST_USER);
		$this->assertEquals(1, count($books));
		$bookId = $books[0]['id'];

		// create a card
		$uri0 = self::getUniqueID('card');
		$this->backend->createCard($bookId, $uri0, $this->vcardTest0);
		$uri1 = self::getUniqueID('card');
		$this->backend->createCard($bookId, $uri1, $this->vcardTest1);
		$uri2 = self::getUniqueID('card');
		$this->backend->createCard($bookId, $uri2, $this->vcardTest2);

		// get all the cards
		$cards = $this->backend->getCards($bookId);
		$this->assertEquals(3, count($cards));
		usort($cards, function ($a, $b) {
			return $a['id'] < $b['id'] ? -1 : 1;
		});

		$this->assertEquals($this->vcardTest0, $cards[0]['carddata']);
		$this->assertEquals($this->vcardTest1, $cards[1]['carddata']);
		$this->assertEquals($this->vcardTest2, $cards[2]['carddata']);

		// get the cards 1 & 2 (not 0)
		$cards = $this->backend->getMultipleCards($bookId, [$uri1, $uri2]);
		$this->assertEquals(2, count($cards));
		usort($cards, function ($a, $b) {
			return $a['id'] < $b['id'] ? -1 : 1;
		});
		foreach ($cards as $index => $card) {
			$this->assertArrayHasKey('id', $card);
			$this->assertArrayHasKey('uri', $card);
			$this->assertArrayHasKey('lastmodified', $card);
			$this->assertArrayHasKey('etag', $card);
			$this->assertArrayHasKey('size', $card);
			$this->assertEquals($this->{ 'vcardTest' . ($index + 1) }, $card['carddata']);
		}

		// delete the card
		$this->backend->deleteCard($bookId, $uri0);
		$this->backend->deleteCard($bookId, $uri1);
		$this->backend->deleteCard($bookId, $uri2);
		$cards = $this->backend->getCards($bookId);
		$this->assertEquals(0, count($cards));
	}

	public function testMultipleUIDOnDifferentAddressbooks(): void {
		$this->backend = $this->getMockBuilder(CardDavBackend::class)
			->setConstructorArgs([$this->db, $this->principal, $this->userManager, $this->dispatcher, $this->sharingBackend,$this->config])
			->onlyMethods(['updateProperties'])
			->getMock();

		// create 2 new address books
		$this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example2', []);
		$books = $this->backend->getAddressBooksForUser(self::UNIT_TEST_USER);
		$this->assertEquals(2, count($books));
		$bookId0 = $books[0]['id'];
		$bookId1 = $books[1]['id'];

		// create a card
		$uri0 = $this->getUniqueID('card');
		$this->backend->createCard($bookId0, $uri0, $this->vcardTest0);

		// create another card with same uid but in second address book
		$uri1 = $this->getUniqueID('card');
		$this->backend->createCard($bookId1, $uri1, $this->vcardTest0);
	}

	public function testMoveCardToCollectionWithSameUID(): void {
		$this->backend = $this->getMockBuilder(CardDavBackend::class)
			->setConstructorArgs([$this->db, $this->principal, $this->userManager, $this->dispatcher, $this->sharingBackend, $this->config])
			->onlyMethods(['updateProperties'])
			->getMock();

		$this->backend->createAddressBook(self::UNIT_TEST_USER, 'Source', []);
		$this->backend->createAddressBook(self::UNIT_TEST_USER, 'Target', []);
		$books = $this->backend->getAddressBooksForUser(self::UNIT_TEST_USER);
		$sourceBookId = (int)$books[0]['id'];
		$targetBookId = (int)$books[1]['id'];

		// Same UID in two different collections is allowed; moving the source
		// into the target must then be rejected with the no-uid-conflict error.
		$this->backend->createCard($sourceBookId, 'source.vcf', $this->vcardTest0);
		$this->backend->createCard($targetBookId, 'target.vcf', $this->vcardTest0);

		$this->expectException(UidConflict::class);
		$this->expectExceptionMessage('VCard object with uid already exists in this addressbook collection.');
		$this->backend->moveCard($sourceBookId, 'source.vcf', $targetBookId, 'moved.vcf');
	}

	public function testMultipleUIDDenied(): void {
		$this->backend = $this->getMockBuilder(CardDavBackend::class)
			->setConstructorArgs([$this->db, $this->principal, $this->userManager, $this->dispatcher, $this->sharingBackend, $this->config])
			->onlyMethods(['updateProperties'])
			->getMock();

		// create a new address book
		$this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$books = $this->backend->getAddressBooksForUser(self::UNIT_TEST_USER);
		$this->assertEquals(1, count($books));
		$bookId = $books[0]['id'];

		// create a card
		$uri0 = $this->getUniqueID('card');
		$this->backend->createCard($bookId, $uri0, $this->vcardTest0);

		// create another card with same uid
		$uri1 = $this->getUniqueID('card');
		$this->expectException(UidConflict::class);
		$this->expectExceptionMessage('VCard object with uid already exists in this addressbook collection.');
		$test = $this->backend->createCard($bookId, $uri1, $this->vcardTest0);
	}

	public function testNoValidUID(): void {
		$this->backend = $this->getMockBuilder(CardDavBackend::class)
			->setConstructorArgs([$this->db, $this->principal, $this->userManager, $this->dispatcher, $this->sharingBackend, $this->config])
			->onlyMethods(['updateProperties'])
			->getMock();

		// create a new address book
		$this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$books = $this->backend->getAddressBooksForUser(self::UNIT_TEST_USER);
		$this->assertEquals(1, count($books));
		$bookId = $books[0]['id'];

		// create a card without uid
		$uri1 = $this->getUniqueID('card');
		$this->expectException(BadRequest::class);
		$test = $this->backend->createCard($bookId, $uri1, $this->vcardTestNoUID);
	}

	public function testDeleteWithoutCard(): void {
		$this->backend = $this->getMockBuilder(CardDavBackend::class)
			->setConstructorArgs([$this->db, $this->principal, $this->userManager, $this->dispatcher, $this->sharingBackend, $this->config])
			->onlyMethods([
				'getCardId',
				'addChange',
				'purgeProperties',
				'updateProperties',
			])
			->getMock();

		// create a new address book
		$this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$books = $this->backend->getUsersOwnAddressBooks(self::UNIT_TEST_USER);
		$this->assertEquals(1, count($books));

		$bookId = $books[0]['id'];
		$uri = $this->getUniqueID('card');

		// create a new address book
		$this->backend->expects($this->once())
			->method('getCardId')
			->with($bookId, $uri)
			->willThrowException(new \InvalidArgumentException());

		$calls = [
			[$bookId, $uri, 1],
			[$bookId, $uri, 3],
		];
		$this->backend->expects($this->exactly(count($calls)))
			->method('addChange')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});
		$this->backend->expects($this->never())
			->method('purgeProperties');

		// create a card
		$this->backend->createCard($bookId, $uri, $this->vcardTest0);

		// delete the card
		$this->assertTrue($this->backend->deleteCard($bookId, $uri));
	}

	public function testDeleteCardWithoutMatchingRowRecordsNoChange(): void {
		$this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$books = $this->backend->getUsersOwnAddressBooks(self::UNIT_TEST_USER);
		$bookId = (int)$books[0]['id'];

		$this->assertFalse($this->backend->deleteCard($bookId, 'does-not-exist.vcf'));

		$this->assertSame(0, $this->countChanges($bookId));
		$this->assertSame(1, $this->getSyncToken($bookId));
	}

	public function testUpdateCardWithUnchangedDataRecordsNoChange(): void {
		$this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$books = $this->backend->getUsersOwnAddressBooks(self::UNIT_TEST_USER);
		$bookId = (int)$books[0]['id'];

		$uri = $this->getUniqueID('card');
		$this->backend->createCard($bookId, $uri, $this->vcardTest0);
		$changesAfterCreate = $this->countChanges($bookId);
		$syncTokenAfterCreate = $this->getSyncToken($bookId);

		$etag = $this->createBackend()->updateCard($bookId, $uri, $this->vcardTest0);

		$this->assertEquals('"' . md5($this->vcardTest0) . '"', $etag);
		$this->assertSame($changesAfterCreate, $this->countChanges($bookId));
		$this->assertSame($syncTokenAfterCreate, $this->getSyncToken($bookId));
	}

	public function testUpdateCardWithChangedDataRecordsChange(): void {
		$this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$books = $this->backend->getUsersOwnAddressBooks(self::UNIT_TEST_USER);
		$bookId = (int)$books[0]['id'];

		$uri = $this->getUniqueID('card');
		$this->backend->createCard($bookId, $uri, $this->vcardTest0);
		$changesAfterCreate = $this->countChanges($bookId);
		$syncTokenAfterCreate = $this->getSyncToken($bookId);

		$this->createBackend()->updateCard($bookId, $uri, $this->vcardTest1);

		$this->assertSame($changesAfterCreate + 1, $this->countChanges($bookId));
		$this->assertSame($syncTokenAfterCreate + 1, $this->getSyncToken($bookId));
	}

	/**
	 * Federated full syncs null `lastmodified` up front and delete whatever is
	 * still null afterwards, so an unchanged card must still refresh the column.
	 */
	public function testUpdateCardWithUnchangedDataClearsPendingState(): void {
		$this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$books = $this->backend->getUsersOwnAddressBooks(self::UNIT_TEST_USER);
		$bookId = (int)$books[0]['id'];

		$uri = $this->getUniqueID('card');
		$this->backend->createCard($bookId, $uri, $this->vcardTest0);
		$this->backend->markCardsAsPending($bookId);
		$this->assertCount(1, $this->backend->getPendingCards($bookId));

		$this->createBackend()->updateCard($bookId, $uri, $this->vcardTest0);

		$this->assertEmpty($this->backend->getPendingCards($bookId));
	}

	public function testSyncSupport(): void {
		$this->backend = $this->getMockBuilder(CardDavBackend::class)
			->setConstructorArgs([$this->db, $this->principal, $this->userManager, $this->dispatcher, $this->sharingBackend, $this->config])
			->onlyMethods(['updateProperties'])
			->getMock();

		// create a new address book
		$this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$books = $this->backend->getAddressBooksForUser(self::UNIT_TEST_USER);
		$this->assertEquals(1, count($books));
		$bookId = $books[0]['id'];

		// fist call without synctoken
		$changes = $this->backend->getChangesForAddressBook($bookId, '', 1);
		$syncToken = $changes['syncToken'];

		// add a change
		$uri0 = $this->getUniqueID('card');
		$this->backend->createCard($bookId, $uri0, $this->vcardTest0);

		// look for changes
		$changes = $this->backend->getChangesForAddressBook($bookId, $syncToken, 1);
		$this->assertEquals($uri0, $changes['added'][0]);
	}

	public function testSyncSupportTruncatesIncrementalChanges(): void {
		$this->config->method('getSystemValueInt')
			->willReturnCallback(fn (string $key, int $default) => $key === 'carddav_sync_request_truncation' ? 2 : $default);

		$bookId = $this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$syncToken = $this->backend->getChangesForAddressBook($bookId, '', 1)['syncToken'];

		$uris = [];
		for ($i = 0; $i < 3; $i++) {
			$uri = $this->getUniqueID('card');
			$this->backend->createCard($bookId, $uri, str_replace('UID:Test', 'UID:Test' . $i, $this->vcardTest0));
			$uris[] = $uri;
		}

		$changes = $this->backend->getChangesForAddressBook($bookId, (string)$syncToken, 1, null);
		$this->assertTrue($changes['result_truncated']);
		$this->assertSame([$uris[0], $uris[1]], $changes['added']);

		$changes = $this->backend->getChangesForAddressBook($bookId, (string)$changes['syncToken'], 1, null);
		$this->assertTrue($changes['result_truncated']);
		$this->assertSame([$uris[1], $uris[2]], $changes['added']);

		$changes = $this->backend->getChangesForAddressBook($bookId, (string)$changes['syncToken'], 1, null);
		$this->assertArrayNotHasKey('result_truncated', $changes);
		$this->assertSame([$uris[2]], $changes['added']);
	}

	public function testSharing(): void {
		$this->userManager->expects($this->any())
			->method('userExists')
			->willReturn(true);
		$this->groupManager->expects($this->any())
			->method('groupExists')
			->willReturn(true);
		$this->principal->expects(self::any())
			->method('findByUri')
			->willReturn(self::UNIT_TEST_USER1);

		$this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$books = $this->backend->getAddressBooksForUser(self::UNIT_TEST_USER);
		$this->assertEquals(1, count($books));

		$l = $this->createMock(IL10N::class);
		$exampleBook = new AddressBook($this->backend, $books[0], $l);
		$this->backend->updateShares($exampleBook, [['href' => 'principal:' . self::UNIT_TEST_USER1]], []);

		$shares = $this->backend->getShares($exampleBook->getResourceId());
		$this->assertEquals(1, count($shares));

		// adding the same sharee again has no effect
		$this->backend->updateShares($exampleBook, [['href' => 'principal:' . self::UNIT_TEST_USER1]], []);

		$shares = $this->backend->getShares($exampleBook->getResourceId());
		$this->assertEquals(1, count($shares));

		$books = $this->backend->getAddressBooksForUser(self::UNIT_TEST_USER1);
		$this->assertEquals(1, count($books));

		$this->backend->updateShares($exampleBook, [], ['principal:' . self::UNIT_TEST_USER1]);

		$shares = $this->backend->getShares($exampleBook->getResourceId());
		$this->assertEquals(0, count($shares));

		$books = $this->backend->getAddressBooksForUser(self::UNIT_TEST_USER1);
		$this->assertEquals(0, count($books));
	}

	public function testUpdateProperties(): void {
		$bookId = 42;
		$cardUri = 'card-uri';
		$cardId = 2;

		$backend = $this->getMockBuilder(CardDavBackend::class)
			->setConstructorArgs([$this->db, $this->principal, $this->userManager, $this->dispatcher, $this->sharingBackend, $this->config])
			->onlyMethods(['getCardId'])->getMock();

		$backend->expects($this->any())->method('getCardId')->willReturn($cardId);

		// add properties for new vCard
		$vCard = new VCard();
		$vCard->UID = $cardUri;
		$vCard->FN = 'John Doe';
		$this->invokePrivate($backend, 'updateProperties', [$bookId, $cardUri, $vCard->serialize()]);

		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('cards_properties')
			->orderBy('name');

		$qResult = $query->executeQuery();
		$result = $qResult->fetchAllAssociative();
		$qResult->closeCursor();

		$this->assertSame(2, count($result));

		$this->assertSame('FN', $result[0]['name']);
		$this->assertSame('John Doe', $result[0]['value']);
		$this->assertSame($bookId, (int)$result[0]['addressbookid']);
		$this->assertSame($cardId, (int)$result[0]['cardid']);

		$this->assertSame('UID', $result[1]['name']);
		$this->assertSame($cardUri, $result[1]['value']);
		$this->assertSame($bookId, (int)$result[1]['addressbookid']);
		$this->assertSame($cardId, (int)$result[1]['cardid']);

		// update properties for existing vCard
		$vCard = new VCard();
		$vCard->UID = $cardUri;
		$this->invokePrivate($backend, 'updateProperties', [$bookId, $cardUri, $vCard->serialize()]);

		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('cards_properties');

		$qResult = $query->executeQuery();
		$result = $qResult->fetchAllAssociative();
		$qResult->closeCursor();

		$this->assertSame(1, count($result));

		$this->assertSame('UID', $result[0]['name']);
		$this->assertSame($cardUri, $result[0]['value']);
		$this->assertSame($bookId, (int)$result[0]['addressbookid']);
		$this->assertSame($cardId, (int)$result[0]['cardid']);
	}

	public function testPurgeProperties(): void {
		$query = $this->db->getQueryBuilder();
		$query->insert('cards_properties')
			->values(
				[
					'addressbookid' => $query->createNamedParameter(1),
					'cardid' => $query->createNamedParameter(1),
					'name' => $query->createNamedParameter('name1'),
					'value' => $query->createNamedParameter('value1'),
					'preferred' => $query->createNamedParameter(0)
				]
			);
		$query->executeStatement();

		$query = $this->db->getQueryBuilder();
		$query->insert('cards_properties')
			->values(
				[
					'addressbookid' => $query->createNamedParameter(1),
					'cardid' => $query->createNamedParameter(2),
					'name' => $query->createNamedParameter('name2'),
					'value' => $query->createNamedParameter('value2'),
					'preferred' => $query->createNamedParameter(0)
				]
			);
		$query->executeStatement();

		$this->invokePrivate($this->backend, 'purgeProperties', [1, 1]);

		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('cards_properties');

		$qResult = $query->executeQuery();
		$result = $qResult->fetchAllAssociative();
		$qResult->closeCursor();

		$this->assertSame(1, count($result));
		$this->assertSame(1, (int)$result[0]['addressbookid']);
		$this->assertSame(2, (int)$result[0]['cardid']);
	}

	public function testGetCardId(): void {
		$query = $this->db->getQueryBuilder();

		$query->insert('cards')
			->values(
				[
					'addressbookid' => $query->createNamedParameter(1),
					'carddata' => $query->createNamedParameter(''),
					'uri' => $query->createNamedParameter('uri'),
					'lastmodified' => $query->createNamedParameter(4738743),
					'etag' => $query->createNamedParameter('etag'),
					'size' => $query->createNamedParameter(120)
				]
			);
		$query->executeStatement();
		$id = $query->getLastInsertId();

		$this->assertSame($id,
			$this->invokePrivate($this->backend, 'getCardId', [1, 'uri']));
	}

	public function testGetCardIdFailed(): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->invokePrivate($this->backend, 'getCardId', [1, 'uri']);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestSearch')]
	public function testSearch(string $pattern, array $properties, array $options, array $expectedUris, array $expectedNeedles): void {
		$vCards = [];

		$vCards[0] = new VCard();
		$vCards[0]->add(new Text($vCards[0], 'UID', 'uid-0'));
		$vCards[0]->add(new Text($vCards[0], 'FN', 'John Doe'));
		$vCards[0]->add(new Text($vCards[0], 'CLOUD', 'john@nextcloud.com'));

		$vCards[1] = new VCard();
		$vCards[1]->add(new Text($vCards[1], 'UID', 'uid-1'));
		$vCards[1]->add(new Text($vCards[1], 'FN', 'John M. Doe'));

		$vCards[2] = new VCard();
		$vCards[2]->add(new Text($vCards[2], 'UID', 'uid-2'));
		$vCards[2]->add(new Text($vCards[2], 'FN', 'find without options'));
		$vCards[2]->add(new Text($vCards[2], 'CLOUD', 'peter_pan@nextcloud.com'));

		$vCardIds = [];
		$query = $this->db->getQueryBuilder();
		for ($i = 0; $i < 3; $i++) {
			$query->insert($this->dbCardsTable)
				->values(
					[
						'addressbookid' => $query->createNamedParameter(0),
						'carddata' => $query->createNamedParameter($vCards[$i]->serialize(), IQueryBuilder::PARAM_LOB),
						'uri' => $query->createNamedParameter('uri' . $i),
						'lastmodified' => $query->createNamedParameter(time()),
						'etag' => $query->createNamedParameter('etag' . $i),
						'size' => $query->createNamedParameter(120),
					]
				);
			$query->executeStatement();
			$vCardIds[] = $query->getLastInsertId();
		}

		$propertyRows = [
			[$vCardIds[0], 'FN', 'John Doe'],
			[$vCardIds[0], 'CLOUD', 'John@nextcloud.com'],
			[$vCardIds[1], 'FN', 'John M. Doe'],
			[$vCardIds[2], 'FN', 'find without options'],
			[$vCardIds[2], 'CLOUD', 'peter_pan@nextcloud.com'],
		];

		foreach ($propertyRows as [$cardId, $name, $value]) {
			$query = $this->db->getQueryBuilder();
			$query->insert($this->dbCardsPropertiesTable)
				->values(
					[
						'addressbookid' => $query->createNamedParameter(0),
						'cardid' => $query->createNamedParameter($cardId),
						'name' => $query->createNamedParameter($name),
						'value' => $query->createNamedParameter($value),
						'preferred' => $query->createNamedParameter(0),
					]
				);
			$query->executeStatement();
		}

		$result = $this->backend->search(0, $pattern, $properties, $options);

		$this->assertCount(count($expectedUris), $result);

		$actualUris = array_map(static fn (array $row): string => $row['uri'], $result);
		sort($actualUris);
		$expectedSortedUris = $expectedUris;
		sort($expectedSortedUris);

		$this->assertSame($expectedSortedUris, $actualUris, 'Search returned unexpected URIs');

		$expectedByUri = array_combine($expectedUris, $expectedNeedles);
		$this->assertIsArray($expectedByUri);

		foreach ($result as $row) {
			$this->assertArrayHasKey($row['uri'], $expectedByUri, 'Unexpected URI in search result');
			$this->assertNotFalse(
				strpos($row['carddata'], $expectedByUri[$row['uri']]),
				'Returned carddata does not contain expected fragment for ' . $row['uri']
			);
		}
	}

	public static function dataTestSearch(): array {
		return [
			'basic FN match' => [
				'pattern' => 'John',
				'properties' => ['FN'],
				'options' => [],
				'expectedUris' => ['uri0', 'uri1'],
				'expectedNeedles' => ['John Doe', 'John M. Doe'],
			],
			'partial FN match' => [
				'pattern' => 'M. Doe',
				'properties' => ['FN'],
				'options' => [],
				'expectedUris' => ['uri1'],
				'expectedNeedles' => ['John M. Doe'],
			],
			'substring FN match' => [
				'pattern' => 'Do',
				'properties' => ['FN'],
				'options' => [],
				'expectedUris' => ['uri0', 'uri1'],
				'expectedNeedles' => ['John Doe', 'John M. Doe'],
			],
			'search across multiple properties returns one result per card' => [
				'pattern' => 'John',
				'properties' => ['FN', 'CLOUD'],
				'options' => [],
				'expectedUris' => ['uri0', 'uri1'],
				'expectedNeedles' => ['John Doe', 'John M. Doe'],
			],
			'case-insensitive search' => [
				'pattern' => 'john',
				'properties' => ['FN'],
				'options' => [],
				'expectedUris' => ['uri0', 'uri1'],
				'expectedNeedles' => ['John Doe', 'John M. Doe'],
			],
			'limit' => [
				'pattern' => 'john',
				'properties' => ['FN'],
				'options' => ['limit' => 1],
				'expectedUris' => ['uri0'],
				'expectedNeedles' => ['John Doe'],
			],
			'limit with offset' => [
				'pattern' => 'john',
				'properties' => ['FN'],
				'options' => ['limit' => 1, 'offset' => 1],
				'expectedUris' => ['uri1'],
				'expectedNeedles' => ['John M. Doe'],
			],
			'underscore is escaped by default' => [
				'pattern' => '_',
				'properties' => ['CLOUD'],
				'options' => [],
				'expectedUris' => ['uri2'],
				'expectedNeedles' => ['find without options'],
			],
			'underscore wildcard search when escape_like_param is false' => [
				'pattern' => '%_%',
				'properties' => ['CLOUD'],
				'options' => ['escape_like_param' => false],
				'expectedUris' => ['uri0', 'uri2'],
				'expectedNeedles' => ['John Doe', 'find without options'],
			],
		];
	}

	public function testGetCardUri(): void {
		$query = $this->db->getQueryBuilder();
		$query->insert($this->dbCardsTable)
			->values(
				[
					'addressbookid' => $query->createNamedParameter(1),
					'carddata' => $query->createNamedParameter('carddata', IQueryBuilder::PARAM_LOB),
					'uri' => $query->createNamedParameter('uri'),
					'lastmodified' => $query->createNamedParameter(5489543),
					'etag' => $query->createNamedParameter('etag'),
					'size' => $query->createNamedParameter(120),
				]
			);
		$query->executeStatement();

		$id = $query->getLastInsertId();

		$this->assertSame('uri', $this->backend->getCardUri($id));
	}

	public function testGetCardUriFailed(): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->backend->getCardUri(1);
	}

	public function testGetContact(): void {
		$query = $this->db->getQueryBuilder();
		for ($i = 0; $i < 2; $i++) {
			$query->insert($this->dbCardsTable)
				->values(
					[
						'addressbookid' => $query->createNamedParameter($i),
						'carddata' => $query->createNamedParameter('carddata' . $i, IQueryBuilder::PARAM_LOB),
						'uri' => $query->createNamedParameter('uri' . $i),
						'lastmodified' => $query->createNamedParameter(5489543),
						'etag' => $query->createNamedParameter('etag' . $i),
						'size' => $query->createNamedParameter(120),
					]
				);
			$query->executeStatement();
		}

		$result = $this->backend->getContact(0, 'uri0');
		$this->assertSame(8, count($result));
		$this->assertSame(0, (int)$result['addressbookid']);
		$this->assertSame('uri0', $result['uri']);
		$this->assertSame(5489543, (int)$result['lastmodified']);
		$this->assertSame('"etag0"', $result['etag']);
		$this->assertSame(120, (int)$result['size']);

		// this shouldn't return any result because 'uri1' is in address book 1
		// see https://github.com/nextcloud/server/issues/229
		$result = $this->backend->getContact(0, 'uri1');
		$this->assertEmpty($result);
	}

	public function testGetContactFail(): void {
		$this->assertEmpty($this->backend->getContact(0, 'uri'));
	}

	public function testCollectCardProperties(): void {
		$query = $this->db->getQueryBuilder();
		$query->insert($this->dbCardsPropertiesTable)
			->values(
				[
					'addressbookid' => $query->createNamedParameter(666),
					'cardid' => $query->createNamedParameter(777),
					'name' => $query->createNamedParameter('FN'),
					'value' => $query->createNamedParameter('John Doe'),
					'preferred' => $query->createNamedParameter(0)
				]
			)
			->executeStatement();

		$result = $this->backend->collectCardProperties(666, 'FN');
		$this->assertEquals(['John Doe'], $result);
	}

	/**
	 * @throws \OCP\DB\Exception
	 * @throws \Sabre\DAV\Exception\BadRequest
	 */
	public function testPruneOutdatedSyncTokens(): void {
		$addressBookId = $this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$changes = $this->backend->getChangesForAddressBook($addressBookId, '', 1);
		$syncToken = $changes['syncToken'];

		$uri = $this->getUniqueID('card');
		$this->backend->createCard($addressBookId, $uri, $this->vcardTest0);
		$this->backend->updateCard($addressBookId, $uri, $this->vcardTest1);

		// Keep everything by using a retention duration larger than the row age
		$deleted = $this->backend->pruneOutdatedSyncTokens(0, time());
		self::assertSame(0, $deleted);

		// A retention duration of 0 means everything older than "now" is eligible
		$deleted = $this->backend->pruneOutdatedSyncTokens(0, 0);
		// At least one from the object creation and one from the object update
		$this->assertGreaterThanOrEqual(2, $deleted);
		$changes = $this->backend->getChangesForAddressBook($addressBookId, $syncToken, 1);
		$this->assertEmpty($changes['added']);
		$this->assertEmpty($changes['modified']);
		$this->assertEmpty($changes['deleted']);

		// Test that objects remain

		// Currently changes are empty
		$changes = $this->backend->getChangesForAddressBook($addressBookId, $syncToken, 100);
		$this->assertEquals(0, count($changes['added'] + $changes['modified'] + $changes['deleted']));

		// Create card
		$uri = $this->getUniqueID('card');
		$this->backend->createCard($addressBookId, $uri, $this->vcardTest0);
		// We now have one add
		$changes = $this->backend->getChangesForAddressBook($addressBookId, $syncToken, 100);
		$this->assertEquals(1, count($changes['added']));
		$this->assertEmpty($changes['modified']);
		$this->assertEmpty($changes['deleted']);

		// Update card
		$this->backend->updateCard($addressBookId, $uri, $this->vcardTest1);
		// One add, one modify, but shortened to modify
		$changes = $this->backend->getChangesForAddressBook($addressBookId, $syncToken, 100);
		$this->assertEmpty($changes['added']);
		$this->assertEquals(1, count($changes['modified']));
		$this->assertEmpty($changes['deleted']);

		// Delete all but last change
		$deleted = $this->backend->pruneOutdatedSyncTokens(1, 0);
		$this->assertEquals(1, $deleted); // We had two changes before, now one

		// Only update should remain
		$changes = $this->backend->getChangesForAddressBook($addressBookId, $syncToken, 100);
		$this->assertEmpty($changes['added']);
		$this->assertEquals(1, count($changes['modified']));
		$this->assertEmpty($changes['deleted']);

		// Check that no crash occurs when prune is called without current changes
		$deleted = $this->backend->pruneOutdatedSyncTokens(1, 0);
	}

	/**
	 * @param list<string> $properties vCard content lines
	 */
	private function createQueryCard(int $addressBookId, string $uri, array $properties): void {
		$this->backend->createCard($addressBookId, $uri, implode("\r\n", [
			'BEGIN:VCARD',
			'VERSION:3.0',
			'UID:' . $uri,
			...$properties,
			'END:VCARD',
		]) . "\r\n");
	}

	private static function propFilter(string $name, array $textMatches = [], string $test = 'anyof', array $paramFilters = [], bool $isNotDefined = false): array {
		return [
			'name' => $name,
			'test' => $test,
			'is-not-defined' => $isNotDefined,
			'param-filters' => $paramFilters,
			'text-matches' => $textMatches,
		];
	}

	private static function textMatch(string $value, string $matchType = 'contains', bool $negate = false, string $collation = 'i;unicode-casemap'): array {
		return [
			'negate-condition' => $negate,
			'collation' => $collation,
			'match-type' => $matchType,
			'value' => $value,
		];
	}

	/**
	 * @param array{cards: list<array{name: string, continuationToken: string}>, hasMore: bool} $result
	 * @return list<string>
	 */
	private static function queryNames(array $result): array {
		return array_column($result['cards'], 'name');
	}

	/**
	 * Walks all pages, continuing after the last card of each page
	 *
	 * @param callable(?string): array{cards: list<array{name: string, continuationToken: string}>, hasMore: bool} $query
	 * @return list<string>
	 */
	private static function collectQueryPages(callable $query, int $limit): array {
		$names = [];
		$token = null;
		$pages = 0;
		do {
			$page = $query($token);
			self::assertLessThanOrEqual($limit, count($page['cards']));
			$names = [...$names, ...self::queryNames($page)];
			if ($page['hasMore']) {
				self::assertNotEmpty($page['cards']);
				$token = $page['cards'][array_key_last($page['cards'])]['continuationToken'];
			}
			self::assertLessThan(100, ++$pages, 'Pagination does not terminate');
		} while ($page['hasMore']);
		return $names;
	}

	public function testQueryCardsOrdersByFnCaseInsensitively(): void {
		$bookId = $this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$otherBookId = $this->backend->createAddressBook(self::UNIT_TEST_USER, 'Other', []);
		$this->createQueryCard($bookId, 'charlie.vcf', ['FN:CHARLIE']);
		$this->createQueryCard($bookId, 'nofn1.vcf', ['N:Nobody;;;;']);
		$this->createQueryCard($bookId, 'alpha.vcf', ['FN:Alpha']);
		$this->createQueryCard($bookId, 'multi.vcf', ['FN:Zulu', 'FN:able']);
		$this->createQueryCard($bookId, 'bravo.vcf', ['FN:bravo']);
		$this->createQueryCard($bookId, 'nofn2.vcf', ['EMAIL:nobody@example.com']);
		$this->createQueryCard($otherBookId, 'other.vcf', ['FN:Aaron']);

		$result = $this->backend->queryCards($bookId, [], 'anyof', null, null);

		self::assertSame(['multi.vcf', 'alpha.vcf', 'bravo.vcf', 'charlie.vcf', 'nofn1.vcf', 'nofn2.vcf'], self::queryNames($result));
		self::assertFalse($result['hasMore']);
	}

	public static function providesQueryCardsPageSizes(): array {
		return [[1], [2], [3], [7], [8], [9]];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'providesQueryCardsPageSizes')]
	public function testQueryCardsPagesCompleteTheResult(int $limit): void {
		$bookId = $this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		// Equal sort keys and cards without FN make the card id decide the order
		$this->createQueryCard($bookId, 'doe1.vcf', ['FN:Doe']);
		$this->createQueryCard($bookId, 'nofn1.vcf', ['N:Nobody;;;;']);
		$this->createQueryCard($bookId, 'doe2.vcf', ['FN:doe']);
		$this->createQueryCard($bookId, 'adams.vcf', ['FN:Adams']);
		$this->createQueryCard($bookId, 'doe3.vcf', ['FN:DOE']);
		$this->createQueryCard($bookId, 'nofn2.vcf', ['N:Nobody;;;;']);
		$this->createQueryCard($bookId, 'multi.vcf', ['FN:Miller', 'FN:Baker']);
		$this->createQueryCard($bookId, 'zimmer.vcf', ['FN:Zimmer']);

		$all = self::queryNames($this->backend->queryCards($bookId, [], 'anyof', null, null));
		self::assertSame(['adams.vcf', 'multi.vcf', 'doe1.vcf', 'doe2.vcf', 'doe3.vcf', 'zimmer.vcf', 'nofn1.vcf', 'nofn2.vcf'], $all);

		$paged = self::collectQueryPages(fn (?string $token) => $this->backend->queryCards($bookId, [], 'anyof', $limit, $token), $limit);

		self::assertSame($all, $paged);
	}

	public function testQueryCardsContinuesAfterEveryCard(): void {
		$bookId = $this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$this->createQueryCard($bookId, 'b1.vcf', ['FN:B']);
		$this->createQueryCard($bookId, 'nofn1.vcf', ['N:Nobody;;;;']);
		$this->createQueryCard($bookId, 'a.vcf', ['FN:a']);
		$this->createQueryCard($bookId, 'b2.vcf', ['FN:b']);
		$this->createQueryCard($bookId, 'nofn2.vcf', ['N:Nobody;;;;']);

		$cards = $this->backend->queryCards($bookId, [], 'anyof', null, null)['cards'];
		$names = array_column($cards, 'name');
		self::assertSame(['a.vcf', 'b1.vcf', 'b2.vcf', 'nofn1.vcf', 'nofn2.vcf'], $names);

		foreach ($cards as $index => $card) {
			$rest = $this->backend->queryCards($bookId, [], 'anyof', null, $card['continuationToken']);
			self::assertSame(array_slice($names, $index + 1), self::queryNames($rest), 'Continuing after ' . $card['name']);
			self::assertFalse($rest['hasMore']);
		}
	}

	public function testQueryCardsHasMoreOnlyWhenMoreCardsExist(): void {
		$bookId = $this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$this->createQueryCard($bookId, 'a.vcf', ['FN:A']);
		$this->createQueryCard($bookId, 'b.vcf', ['FN:B']);
		$this->createQueryCard($bookId, 'c.vcf', ['FN:C']);

		$complete = $this->backend->queryCards($bookId, [], 'anyof', 3, null);
		self::assertSame(['a.vcf', 'b.vcf', 'c.vcf'], self::queryNames($complete));
		self::assertFalse($complete['hasMore']);

		$first = $this->backend->queryCards($bookId, [], 'anyof', 2, null);
		self::assertSame(['a.vcf', 'b.vcf'], self::queryNames($first));
		self::assertTrue($first['hasMore']);
		$last = $this->backend->queryCards($bookId, [], 'anyof', 2, $first['cards'][1]['continuationToken']);
		self::assertSame(['c.vcf'], self::queryNames($last));
		self::assertFalse($last['hasMore']);
		// A token without a limit continues to the end
		self::assertSame(['c.vcf'], self::queryNames($this->backend->queryCards($bookId, [], 'anyof', null, $first['cards'][1]['continuationToken'])));
		// The token of the last card continues to an empty result
		self::assertSame(['cards' => [], 'hasMore' => false], $this->backend->queryCards($bookId, [], 'anyof', 2, $last['cards'][0]['continuationToken']));
	}

	public function testQueryCardsWithZeroLimit(): void {
		$bookId = $this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		self::assertSame(['cards' => [], 'hasMore' => false], $this->backend->queryCards($bookId, [], 'anyof', 0, null));

		$this->createQueryCard($bookId, 'a.vcf', ['FN:A']);
		$this->createQueryCard($bookId, 'b.vcf', ['FN:B']);

		self::assertSame(['cards' => [], 'hasMore' => true], $this->backend->queryCards($bookId, [], 'anyof', 0, null));
	}

	public function testQueryCardsPagesWithFilter(): void {
		$bookId = $this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$this->createQueryCard($bookId, 'doe1.vcf', ['FN:John Doe']);
		$this->createQueryCard($bookId, 'roe.vcf', ['FN:Richard Roe']);
		$this->createQueryCard($bookId, 'doe2.vcf', ['FN:Jane Doe']);
		$this->createQueryCard($bookId, 'doe3.vcf', ['FN:Doe']);
		$filters = [self::propFilter('FN', [self::textMatch('doe')])];

		$paged = self::collectQueryPages(fn (?string $token) => $this->backend->queryCards($bookId, $filters, 'anyof', 1, $token), 1);

		self::assertSame(['doe3.vcf', 'doe2.vcf', 'doe1.vcf'], $paged);
	}

	public static function providesInvalidQueryContinuationTokens(): array {
		$encode = static fn (string $json): string => rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
		return [
			'empty' => [''],
			'not base64url' => ['a+b/c='],
			'padded' => [$encode('["a",1]') . '=='],
			'not json' => [$encode('not json')],
			'object' => [$encode('{"0":"a","1":1}')],
			'one element' => [$encode('["a"]')],
			'three elements' => [$encode('["a",1,2]')],
			'numeric sort key' => [$encode('[1,1]')],
			'nested sort key' => [$encode('[["a"],1]')],
			'string id' => [$encode('["a","1"]')],
			'float id' => [$encode('["a",1.5]')],
			'negative id' => [$encode('["a",-1]')],
			'non-canonical json' => [$encode('[ "a", 1 ]')],
			'too long' => [str_repeat('A', 5000)],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'providesInvalidQueryContinuationTokens')]
	public function testQueryCardsRejectsInvalidContinuationToken(string $token): void {
		$bookId = $this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);

		$this->expectException(BadRequest::class);

		$this->backend->queryCards($bookId, [], 'anyof', 10, $token);
	}

	public function testQueryCasemapAsciiSourcesAreComplete(): void {
		$expected = [];
		for ($codePoint = 0x80; $codePoint <= 0x10FFFF; $codePoint++) {
			if ($codePoint >= 0xD800 && $codePoint <= 0xDFFF) {
				continue;
			}
			$character = mb_chr($codePoint, 'UTF-8');
			preg_match_all('/[\x00-\x7F]/', mb_strtoupper($character, 'UTF-8'), $matches);
			foreach (array_unique(array_map('strtoupper', $matches[0])) as $ascii) {
				$expected[$ascii][] = $character;
			}
		}
		ksort($expected);

		$actual = (new \ReflectionClassConstant(CardDavBackend::class, 'QUERY_CASEMAP_ASCII_SOURCES'))->getValue();
		ksort($actual);

		self::assertSame($expected, $actual);
	}

	public static function providesQueryCardsFilters(): array {
		$all = ['anna.vcf', 'bob.vcf', 'carl.vcf', 'dora.vcf', 'eve.vcf', 'fran.vcf', 'gus.vcf'];
		$fnBecker = self::propFilter('FN', [self::textMatch('becker')]);
		$emailExampleCom = self::propFilter('EMAIL', [self::textMatch('example.com')]);
		$typeParamFilter = ['name' => 'TYPE', 'is-not-defined' => false, 'text-match' => null];
		return [
			'no filters' => [[], 'anyof', $all],
			'contains' => [[$fnBecker], 'anyof', ['bob.vcf']],
			'equals' => [[self::propFilter('FN', [self::textMatch('Bob Becker', 'equals')])], 'anyof', ['bob.vcf']],
			'starts-with' => [[self::propFilter('FN', [self::textMatch('BOB', 'starts-with')])], 'anyof', ['bob.vcf']],
			'ends-with' => [[self::propFilter('FN', [self::textMatch('ecker', 'ends-with')])], 'anyof', ['bob.vcf']],
			'lower case property name' => [[self::propFilter('fn', [self::textMatch('becker')])], 'anyof', ['bob.vcf']],
			'octet collation' => [[self::propFilter('FN', [self::textMatch('Becker', 'contains', false, 'i;octet')])], 'anyof', ['bob.vcf']],
			'ascii-casemap collation' => [[self::propFilter('FN', [self::textMatch('BECKER', 'contains', false, 'i;ascii-casemap')])], 'anyof', ['bob.vcf']],
			'negated text-match is not narrowed' => [[self::propFilter('FN', [self::textMatch('becker', 'contains', true)])], 'anyof', $all],
			'non-indexed property is not narrowed' => [[self::propFilter('X-CUSTOM', [self::textMatch('secret')])], 'anyof', $all],
			'is-not-defined is not narrowed' => [[self::propFilter('EMAIL', [], 'anyof', [], true)], 'anyof', $all],
			'anyof' => [[$fnBecker, $emailExampleCom], 'anyof', ['anna.vcf', 'bob.vcf']],
			'allof' => [[$fnBecker, $emailExampleCom], 'allof', []],
			'anyof with a non-narrowable prop-filter' => [[$fnBecker, self::propFilter('X-CUSTOM', [self::textMatch('bob')])], 'anyof', $all],
			'allof with a non-narrowable prop-filter' => [[$fnBecker, self::propFilter('X-CUSTOM', [self::textMatch('bob')])], 'allof', ['bob.vcf']],
			'property defined' => [[self::propFilter('EMAIL')], 'anyof', ['anna.vcf', 'bob.vcf']],
			'param-filter with anyof only requires the property' => [[self::propFilter('EMAIL', [self::textMatch('nomatch')], 'anyof', [$typeParamFilter])], 'anyof', ['anna.vcf', 'bob.vcf']],
			'param-filter with allof keeps the text-match' => [[self::propFilter('EMAIL', [self::textMatch('example.com')], 'allof', [$typeParamFilter])], 'anyof', ['anna.vcf']],
			'text-matches anyof' => [[self::propFilter('EMAIL', [self::textMatch('bob@'), self::textMatch('.com')])], 'anyof', ['anna.vcf', 'bob.vcf']],
			'text-matches allof' => [[self::propFilter('EMAIL', [self::textMatch('bob@'), self::textMatch('example')], 'allof')], 'anyof', ['bob.vcf']],
			'text-matches anyof with a negated one' => [[self::propFilter('EMAIL', [self::textMatch('bob@'), self::textMatch('bob@', 'contains', true)])], 'anyof', ['anna.vcf', 'bob.vcf']],
			'empty needle only requires the property' => [[self::propFilter('NOTE', [self::textMatch('')])], 'anyof', ['bob.vcf', 'dora.vcf']],
			'needle in the truncated part of a value' => [[self::propFilter('NOTE', [self::textMatch('needle')])], 'anyof', ['dora.vcf']],
			'unicode case mapping of ß' => [[self::propFilter('FN', [self::textMatch('STRASSE')])], 'anyof', ['carl.vcf', 'fran.vcf']],
			'unicode case mapping of ſ' => [[self::propFilter('FN', [self::textMatch('wasser')])], 'anyof', ['carl.vcf', 'fran.vcf']],
			'unicode case mapping of ı' => [[self::propFilter('FN', [self::textMatch('tim')])], 'anyof', ['gus.vcf']],
			'ascii needle excludes other cards' => [[self::propFilter('FN', [self::textMatch('Anna')])], 'anyof', ['anna.vcf']],
			'non-ascii needle characters are wildcards' => [[self::propFilter('FN', [self::textMatch('MÜLLER')])], 'anyof', ['anna.vcf']],
			'non-ascii needle characters in the same case' => [[self::propFilter('FN', [self::textMatch('Müller')])], 'anyof', ['anna.vcf']],
			'like wildcard is escaped' => [[self::propFilter('FN', [self::textMatch('100%')])], 'anyof', ['eve.vcf']],
			'like single character wildcard is escaped' => [[self::propFilter('FN', [self::textMatch('b_b')])], 'anyof', []],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'providesQueryCardsFilters')]
	public function testQueryCardsFilters(array $filters, string $test, array $expected): void {
		$bookId = $this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$this->createQueryCard($bookId, 'carl.vcf', ['FN:Carl Straße', 'X-CUSTOM:secret']);
		$this->createQueryCard($bookId, 'anna.vcf', ['FN:Anna Müller', 'EMAIL;TYPE=WORK:anna@example.com', 'TEL:+49 123']);
		$this->createQueryCard($bookId, 'eve.vcf', ['FN:Eve 100%', 'X-CUSTOM:bob']);
		$this->createQueryCard($bookId, 'bob.vcf', ['FN:Bob Becker', 'EMAIL:bob@example.org', 'NOTE:likes coffee']);
		$this->createQueryCard($bookId, 'dora.vcf', ['FN:Dora', 'NOTE:' . str_repeat('x', 300) . 'needle']);
		$this->createQueryCard($bookId, 'gus.vcf', ['FN:Gus Tım']);
		$this->createQueryCard($bookId, 'fran.vcf', ['FN:Fran Waſſer']);

		$result = self::queryNames($this->backend->queryCards($bookId, $filters, $test, null, null));

		self::assertSame($expected, $result);
		// Never miss a card that the CardDAV plugin considers a match
		$plugin = new \Sabre\CardDAV\Plugin();
		foreach (['anna.vcf', 'bob.vcf', 'carl.vcf', 'dora.vcf', 'eve.vcf', 'fran.vcf', 'gus.vcf'] as $uri) {
			if ($plugin->validateFilters($this->backend->getCard($bookId, $uri)['carddata'], $filters, $test)) {
				self::assertContains($uri, $result);
			}
		}
	}

	public function testQueryCardsRestrictsUris(): void {
		$bookId = $this->backend->createAddressBook(self::UNIT_TEST_USER, 'Example', []);
		$this->createQueryCard($bookId, 'Database:a.vcf', ['FN:A']);
		$this->createQueryCard($bookId, 'Guests:g.vcf', ['FN:G']);
		$this->createQueryCard($bookId, 'Database:c.vcf', ['FN:C']);
		$this->createQueryCard($bookId, 'Database:b.vcf', ['FN:B']);
		// More URIs than fit into a single IN list
		$uris = array_map(static fn (int $i): string => 'Database:missing' . $i . '.vcf', range(1, 1500));
		$uris[] = 'Guests:g.vcf';
		$uris[] = 'Database:c.vcf';

		self::assertSame(['Database:c.vcf', 'Guests:g.vcf'], self::queryNames($this->backend->queryCards($bookId, [], 'anyof', null, null, $uris)));
		self::assertSame(['cards' => [], 'hasMore' => false], $this->backend->queryCards($bookId, [], 'anyof', null, null, []));
		self::assertSame(['Database:a.vcf', 'Database:b.vcf', 'Database:c.vcf'], self::queryNames($this->backend->queryCards($bookId, [], 'anyof', null, null, null, 'Guests:')));
	}

	public static function providesSystemAddressBookEnumerationSettings(): array {
		return [
			'full enumeration' => ['yes', 'no', 'no', 'Database', ['Database:alice.vcf', 'Database:bob.vcf', 'Database:carol.vcf']],
			'no enumeration' => ['no', 'no', 'no', 'Database', ['Database:alice.vcf']],
			'phone number restriction' => ['yes', 'no', 'yes', 'Database', ['Database:alice.vcf']],
			'group restriction' => ['yes', 'yes', 'no', 'Database', ['Database:alice.vcf', 'Database:bob.vcf']],
			'group and phone number restriction' => ['yes', 'yes', 'yes', 'Database', ['Database:alice.vcf', 'Database:bob.vcf']],
			'guest' => ['yes', 'no', 'no', 'Guests', ['Guests:alice.vcf']],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'providesSystemAddressBookEnumerationSettings')]
	public function testSystemAddressBookQueryMatchesChildren(string $enumeration, string $groupRestriction, string $phoneRestriction, string $userBackend, array $expected): void {
		$bookId = $this->backend->createAddressBook(self::UNIT_TEST_USER, 'System', []);
		foreach (['Database:alice', 'Guests:alice', 'Database:bob', 'Database:carol', 'Guests:gina'] as $index => $name) {
			$this->createQueryCard($bookId, $name . '.vcf', ['FN:' . $name, 'EMAIL:user' . $index . '@example.com']);
		}
		$config = $this->createMock(IConfig::class);
		$config->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', $enumeration],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', $groupRestriction],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', $phoneRestriction],
			]);
		$createUser = function (string $backend, string $uid): IUser {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
			$user->method('getBackendClassName')->willReturn($backend);
			return $user;
		};
		$user = $createUser($userBackend, 'alice');
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($user);
		$group = $this->createMock(IGroup::class);
		$group->method('getUsers')->willReturn([$user, $createUser('Database', 'bob'), $createUser('Guests', 'gina')]);
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('getUserGroups')->with($user)->willReturn([$group]);
		$addressBook = new SystemAddressbook(
			$this->backend,
			['id' => $bookId, 'uri' => 'system', '{DAV:}displayname' => 'Accounts', 'principaluri' => 'principals/system/system'],
			$this->createMock(IL10N::class),
			$config,
			$userSession,
			null,
			null,
			$groupManager,
		);

		$children = array_map(static fn ($child): string => $child->getName(), $addressBook->getChildren());
		sort($children);
		self::assertSame($expected, $children);

		$names = self::queryNames($addressBook->addressBookQuery([], 'anyof', null, null));
		sort($names);
		self::assertSame($expected, $names);

		$filters = [self::propFilter('EMAIL', [self::textMatch('@example.com')])];
		$paged = self::collectQueryPages(fn (?string $token) => $addressBook->addressBookQuery($filters, 'anyof', 1, $token), 1);
		sort($paged);
		self::assertSame($expected, $paged);
	}
}
