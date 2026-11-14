<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ContactsInteraction\Tests\Db;

use OCA\ContactsInteraction\Db\CardSearchDao;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\SystemAddressBookVisibility;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\UUIDUtil;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class CardSearchDaoTest extends TestCase {
	private CardDavBackend $backend;
	private IUserManager&MockObject $userManager;
	private SystemAddressBookVisibility&MockObject $visibility;
	private CardSearchDao $dao;
	private IUser&MockObject $actor;
	private int $systemAddressBookId;
	private bool $createdSystemAddressBook = false;
	private ?int $userAddressBookId = null;
	/** @var list<array{int, string}> */
	private array $createdCards = [];

	protected function setUp(): void {
		parent::setUp();

		$this->backend = Server::get(CardDavBackend::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->visibility = $this->createMock(SystemAddressBookVisibility::class);
		$this->dao = new CardSearchDao(Server::get(IDBConnection::class), $this->userManager, $this->visibility);

		$this->actor = $this->createMock(IUser::class);
		$this->actor->method('getUID')->willReturn('card-search-dao-actor');

		$systemAddressBook = $this->backend->getAddressBooksByUri('principals/system/system', 'system');
		if ($systemAddressBook === null) {
			$this->systemAddressBookId = $this->backend->createAddressBook('principals/system/system', 'system', []);
			$this->createdSystemAddressBook = true;
		} else {
			$this->systemAddressBookId = (int)$systemAddressBook['id'];
		}
	}

	protected function tearDown(): void {
		foreach ($this->createdCards as [$addressBookId, $uri]) {
			$this->backend->deleteCard($addressBookId, $uri);
		}
		if ($this->userAddressBookId !== null) {
			$this->backend->deleteAddressBook($this->userAddressBookId);
		}
		if ($this->createdSystemAddressBook) {
			$this->backend->deleteAddressBook($this->systemAddressBookId);
		}

		parent::tearDown();
	}

	private function createCard(int $addressBookId, string $uid, string $email): string {
		$cardData = (new VCard([
			'UID' => $uid,
			'FN' => $uid,
			'EMAIL' => $email,
		]))->serialize();
		$uri = UUIDUtil::getUUID() . '.vcf';
		$this->backend->createCard($addressBookId, $uri, $cardData);
		$this->createdCards[] = [$addressBookId, $uri];
		return $cardData;
	}

	private function mockTargetUser(string $uid): IUser&MockObject {
		$target = $this->createMock(IUser::class);
		$target->method('getUID')->willReturn($uid);
		$this->userManager->method('get')->with($uid)->willReturn($target);
		return $target;
	}

	public function testFindExistingInOwnAddressBook(): void {
		$this->userAddressBookId = $this->backend->createAddressBook('principals/users/card-search-dao-actor', 'contacts', []);
		$uid = UUIDUtil::getUUID();
		$cardData = $this->createCard($this->userAddressBookId, $uid, $uid . '@example.com');
		$this->visibility->expects(self::never())->method('isUserCardVisible');

		$this->assertSame($cardData, $this->dao->findExisting($this->actor, null, $uid . '@example.com', null));
	}

	public function testFindExistingInSystemAddressBookWhenVisible(): void {
		$uid = UUIDUtil::getUUID();
		$cardData = $this->createCard($this->systemAddressBookId, $uid, $uid . '@example.com');
		$target = $this->mockTargetUser($uid);
		$this->visibility->expects(self::once())
			->method('isUserCardVisible')
			->with($this->actor, $target)
			->willReturn(true);

		$this->assertSame($cardData, $this->dao->findExisting($this->actor, $uid, null, null));
	}

	public function testFindExistingIgnoresSystemAddressBookCardWhenNotVisible(): void {
		$uid = UUIDUtil::getUUID();
		$this->createCard($this->systemAddressBookId, $uid, $uid . '@example.com');
		$target = $this->mockTargetUser($uid);
		$this->visibility->expects(self::once())
			->method('isUserCardVisible')
			->with($this->actor, $target)
			->willReturn(false);

		$this->assertNull($this->dao->findExisting($this->actor, null, $uid . '@example.com', null));
	}

	public function testFindExistingIgnoresSystemAddressBookCardOfUnknownUser(): void {
		$uid = UUIDUtil::getUUID();
		$this->createCard($this->systemAddressBookId, $uid, $uid . '@example.com');
		$this->userManager->method('get')->with($uid)->willReturn(null);
		$this->visibility->expects(self::never())->method('isUserCardVisible');

		$this->assertNull($this->dao->findExisting($this->actor, $uid, null, null));
	}
}
