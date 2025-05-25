<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\CardDAV;

use OC\KnownUser\KnownUserService;
use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCA\DAV\CardDAV\AddressBook;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\Sharing\Backend;
use OCA\DAV\CardDAV\Sharing\Service;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\Sharing\SharingMapper;
use OCP\Accounts\IAccountManager;
use OCP\App\IAppManager;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
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
 * @group DB
 *
 * @package OCA\DAV\Tests\unit\CardDAV
 */
class CardDavBackendTest extends TestCase {
	private Principal&MockObject $principal;
	private IUserManager&MockObject $userManager;
	private IGroupManager&MockObject $groupManager;
	private IEventDispatcher&MockObject $dispatcher;
	private Backend $sharingBackend;
	private IDBConnection $db;
	private CardDavBackend $backend;
	private string $dbCardsTable = 'cards';
	private string $dbCardsPropertiesTable = 'cards_properties';

	public const UNIT_TEST_USER = 'principals/users/carddav-unit-test';
	public const UNIT_TEST_USER1 = 'principals/users/carddav-unit-test1';
	public const UNIT_TEST_GROUP = 'principals/groups/carddav-unit-test-group';

	private $vcardTest0 = 'BEGIN:VCARD' . PHP_EOL .
		'VERSION:3.0' . PHP_EOL .
		'PRODID:-//Sabre//Sabre VObject 4.1.2//EN' . PHP_EOL .
		'UID:Test' . PHP_EOL .
		'FN:Test' . PHP_EOL .
		'N:Test;;;;' . PHP_EOL .
		'END:VCARD';

	private $vcardTest1 = 'BEGIN:VCARD' . PHP_EOL .
		'VERSION:3.0' . PHP_EOL .
		'PRODID:-//Sabre//Sabre VObject 4.1.2//EN' . PHP_EOL .
		'UID:Test2' . PHP_EOL .
		'FN:Test2' . PHP_EOL .
		'N:Test2;;;;' . PHP_EOL .
		'END:VCARD';

	private $vcardTest2 = 'BEGIN:VCARD' . PHP_EOL .
		'VERSION:3.0' . PHP_EOL .
		'PRODID:-//Sabre//Sabre VObject 4.1.2//EN' . PHP_EOL .
		'UID:Test3' . PHP_EOL .
		'FN:Test3' . PHP_EOL .
		'N:Test3;;;;' . PHP_EOL .
		'END:VCARD';

	private $vcardTestNoUID = 'BEGIN:VCARD' . PHP_EOL .
		'VERSION:3.0' . PHP_EOL .
		'PRODID:-//Sabre//Sabre VObject 4.1.2//EN' . PHP_EOL .
		'FN:TestNoUID' . PHP_EOL .
		'N:TestNoUID;;;;' . PHP_EOL .
		'END:VCARD';

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
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
				$this->createMock(IConfig::class),
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

		$this->db = Server::get(IDBConnection::class);
		$this->sharingBackend = new Backend($this->userManager,
			$this->groupManager,
			$this->principal,
			$this->createMock(ICacheFactory::class),
			new Service(new SharingMapper($this->db)),
			$this->createMock(LoggerInterface::class)
		);

		$this->backend = new CardDavBackend($this->db,
			$this->principal,
			$this->userManager,
			$this->dispatcher,
			$this->sharingBackend,
		);
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
			->setConstructorArgs([$this->db, $this->principal, $this->userManager, $this->dispatcher, $this->sharingBackend])
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
			->willReturnCallback(function () use (&$calls) {
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
			->setConstructorArgs([$this->db, $this->principal, $this->userManager, $this->dispatcher, $this->sharingBackend])
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
			->setConstructorArgs([$this->db, $this->principal, $this->userManager, $this->dispatcher, $this->sharingBackend])
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

	public function testMultipleUIDDenied(): void {
		$this->backend = $this->getMockBuilder(CardDavBackend::class)
			->setConstructorArgs([$this->db, $this->principal, $this->userManager, $this->dispatcher, $this->sharingBackend])
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
		$this->expectException(BadRequest::class);
		$test = $this->backend->createCard($bookId, $uri1, $this->vcardTest0);
	}

	public function testNoValidUID(): void {
		$this->backend = $this->getMockBuilder(CardDavBackend::class)
			->setConstructorArgs([$this->db, $this->principal, $this->userManager, $this->dispatcher, $this->sharingBackend])
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
			->setConstructorArgs([$this->db, $this->principal, $this->userManager, $this->dispatcher, $this->sharingBackend])
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
			->willReturnCallback(function () use (&$calls) {
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

	public function testSyncSupport(): void {
		$this->backend = $this->getMockBuilder(CardDavBackend::class)
			->setConstructorArgs([$this->db, $this->principal, $this->userManager, $this->dispatcher, $this->sharingBackend])
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
			->setConstructorArgs([$this->db, $this->principal, $this->userManager, $this->dispatcher, $this->sharingBackend])
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

		$qResult = $query->execute();
		$result = $qResult->fetchAll();
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

		$qResult = $query->execute();
		$result = $qResult->fetchAll();
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
		$query->execute();

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
		$query->execute();

		$this->invokePrivate($this->backend, 'purgeProperties', [1, 1]);

		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('cards_properties');

		$qResult = $query->execute();
		$result = $qResult->fetchAll();
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
		$query->execute();
		$id = $query->getLastInsertId();

		$this->assertSame($id,
			$this->invokePrivate($this->backend, 'getCardId', [1, 'uri']));
	}


	public function testGetCardIdFailed(): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->invokePrivate($this->backend, 'getCardId', [1, 'uri']);
	}

	/**
	 * @dataProvider dataTestSearch
	 */
	public function testSearch(string $pattern, array $properties, array $options, array $expected): void {
		/** @var VCard $vCards */
		$vCards = [];
		$vCards[0] = new VCard();
		$vCards[0]->add(new Text($vCards[0], 'UID', 'uid'));
		$vCards[0]->add(new Text($vCards[0], 'FN', 'John Doe'));
		$vCards[0]->add(new Text($vCards[0], 'CLOUD', 'john@nextcloud.com'));
		$vCards[1] = new VCard();
		$vCards[1]->add(new Text($vCards[1], 'UID', 'uid'));
		$vCards[1]->add(new Text($vCards[1], 'FN', 'John M. Doe'));
		$vCards[2] = new VCard();
		$vCards[2]->add(new Text($vCards[2], 'UID', 'uid'));
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
			$query->execute();
			$vCardIds[] = $query->getLastInsertId();
		}

		$query = $this->db->getQueryBuilder();
		$query->insert($this->dbCardsPropertiesTable)
			->values(
				[
					'addressbookid' => $query->createNamedParameter(0),
					'cardid' => $query->createNamedParameter($vCardIds[0]),
					'name' => $query->createNamedParameter('FN'),
					'value' => $query->createNamedParameter('John Doe'),
					'preferred' => $query->createNamedParameter(0)
				]
			);
		$query->execute();
		$query = $this->db->getQueryBuilder();
		$query->insert($this->dbCardsPropertiesTable)
			->values(
				[
					'addressbookid' => $query->createNamedParameter(0),
					'cardid' => $query->createNamedParameter($vCardIds[0]),
					'name' => $query->createNamedParameter('CLOUD'),
					'value' => $query->createNamedParameter('John@nextcloud.com'),
					'preferred' => $query->createNamedParameter(0)
				]
			);
		$query->execute();
		$query = $this->db->getQueryBuilder();
		$query->insert($this->dbCardsPropertiesTable)
			->values(
				[
					'addressbookid' => $query->createNamedParameter(0),
					'cardid' => $query->createNamedParameter($vCardIds[1]),
					'name' => $query->createNamedParameter('FN'),
					'value' => $query->createNamedParameter('John M. Doe'),
					'preferred' => $query->createNamedParameter(0)
				]
			);
		$query->execute();
		$query = $this->db->getQueryBuilder();
		$query->insert($this->dbCardsPropertiesTable)
			->values(
				[
					'addressbookid' => $query->createNamedParameter(0),
					'cardid' => $query->createNamedParameter($vCardIds[2]),
					'name' => $query->createNamedParameter('FN'),
					'value' => $query->createNamedParameter('find without options'),
					'preferred' => $query->createNamedParameter(0)
				]
			);
		$query->execute();
		$query = $this->db->getQueryBuilder();
		$query->insert($this->dbCardsPropertiesTable)
			->values(
				[
					'addressbookid' => $query->createNamedParameter(0),
					'cardid' => $query->createNamedParameter($vCardIds[2]),
					'name' => $query->createNamedParameter('CLOUD'),
					'value' => $query->createNamedParameter('peter_pan@nextcloud.com'),
					'preferred' => $query->createNamedParameter(0)
				]
			);
		$query->execute();

		$result = $this->backend->search(0, $pattern, $properties, $options);

		// check result
		$this->assertSame(count($expected), count($result));
		$found = [];
		foreach ($result as $r) {
			foreach ($expected as $exp) {
				if ($r['uri'] === $exp[0] && strpos($r['carddata'], $exp[1]) > 0) {
					$found[$exp[1]] = true;
					break;
				}
			}
		}

		$this->assertSame(count($expected), count($found));
	}

	public static function dataTestSearch(): array {
		return [
			['John', ['FN'], [], [['uri0', 'John Doe'], ['uri1', 'John M. Doe']]],
			['M. Doe', ['FN'], [], [['uri1', 'John M. Doe']]],
			['Do', ['FN'], [], [['uri0', 'John Doe'], ['uri1', 'John M. Doe']]],
			'check if duplicates are handled correctly' => ['John', ['FN', 'CLOUD'], [], [['uri0', 'John Doe'], ['uri1', 'John M. Doe']]],
			'case insensitive' => ['john', ['FN'], [], [['uri0', 'John Doe'], ['uri1', 'John M. Doe']]],
			'limit' => ['john', ['FN'], ['limit' => 1], [['uri0', 'John Doe']]],
			'limit and offset' => ['john', ['FN'], ['limit' => 1, 'offset' => 1], [['uri1', 'John M. Doe']]],
			'find "_" escaped' => ['_', ['CLOUD'], [], [['uri2', 'find without options']]],
			'find not empty CLOUD' => ['%_%', ['CLOUD'], ['escape_like_param' => false], [['uri0', 'John Doe'], ['uri2', 'find without options']]],
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
		$query->execute();

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
			$query->execute();
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
			->execute();

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

		// Do not delete anything if week data as old as ts=0
		$deleted = $this->backend->pruneOutdatedSyncTokens(0, 0);
		self::assertSame(0, $deleted);

		$deleted = $this->backend->pruneOutdatedSyncTokens(0, time());
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
		$deleted = $this->backend->pruneOutdatedSyncTokens(1, time());
		$this->assertEquals(1, $deleted); // We had two changes before, now one

		// Only update should remain
		$changes = $this->backend->getChangesForAddressBook($addressBookId, $syncToken, 100);
		$this->assertEmpty($changes['added']);
		$this->assertEquals(1, count($changes['modified']));
		$this->assertEmpty($changes['deleted']);

		// Check that no crash occurs when prune is called without current changes
		$deleted = $this->backend->pruneOutdatedSyncTokens(1, time());
	}
}
