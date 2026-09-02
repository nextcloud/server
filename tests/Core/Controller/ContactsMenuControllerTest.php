<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Controller;

use OC\Contacts\ContactsMenu\Manager;
use OC\Core\Controller\ContactsMenuController;
use OCP\Contacts\ContactsMenu\IEntry;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Teams\ITeamManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ContactsMenuControllerTest extends TestCase {
	private IUserSession&MockObject $userSession;
	private Manager&MockObject $contactsManager;
	private ITeamManager&MockObject $teamManager;
	private ICacheFactory&MockObject $cacheFactory;
	private ICache&MockObject $cache;

	private ContactsMenuController $controller;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$request = $this->createMock(IRequest::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->contactsManager = $this->createMock(Manager::class);
		$this->teamManager = $this->createMock(ITeamManager::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cache = $this->createMock(ICache::class);

		$this->cacheFactory->method('createDistributed')
			->with('contactsmenu-preview')
			->willReturn($this->cache);

		$this->controller = new ContactsMenuController(
			$request,
			$this->userSession,
			$this->contactsManager,
			$this->teamManager,
			$this->cacheFactory,
		);
	}

	public function testIndex(): void {
		$user = $this->createMock(IUser::class);
		$entries = [
			$this->createMock(IEntry::class),
			$this->createMock(IEntry::class),
		];
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->contactsManager->expects($this->once())
			->method('getEntries')
			->with($this->equalTo($user), $this->equalTo(null))
			->willReturn($entries);

		$response = $this->controller->index();

		$this->assertEquals($entries, $response);
	}

	public function testIndex_withTeam(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('current-user');

		$entries = [
			$this->createMock(IEntry::class),
			$this->createMock(IEntry::class),
		];
		$entries[0]->method('getProperty')
			->with('UID')
			->willReturn('member1');
		$entries[1]->method('getProperty')
			->with('UID')
			->willReturn('member2');

		$this->userSession->expects($this->atLeastOnce())
			->method('getUser')
			->willReturn($user);
		$this->contactsManager->expects($this->once())
			->method('getEntries')
			->with($this->equalTo($user), $this->equalTo(null))
			->willReturn(['contacts' => $entries]);

		$this->teamManager->expects($this->once())
			->method('getMembersOfTeam')
			->with('team-id', 'current-user')
			->willReturn(['member1' => 'Member 1', 'member3' => 'Member 3']);

		$response = $this->controller->index(teamId: 'team-id');

		$this->assertEquals([$entries[0]], $response['contacts']);
	}

	public function testFindOne(): void {
		$user = $this->createMock(IUser::class);
		$entry = $this->createMock(IEntry::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->contactsManager->expects($this->once())
			->method('findOne')
			->with($this->equalTo($user), $this->equalTo(42), $this->equalTo('test-search-phrase'))
			->willReturn($entry);

		$response = $this->controller->findOne(42, 'test-search-phrase');

		$this->assertEquals($entry, $response);
	}

	public function testFindOne404(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->contactsManager->expects($this->once())
			->method('findOne')
			->with($this->equalTo($user), $this->equalTo(42), $this->equalTo('test-search-phrase'))
			->willReturn(null);

		$response = $this->controller->findOne(42, 'test-search-phrase');

		$this->assertEquals([], $response->getData());
		$this->assertEquals(404, $response->getStatus());
	}

	public function testPreviewAvatarsWithoutTeam(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('current-user');

		$contacts = [
			$this->createPreviewEntry('alice', 'Alice'),
			$this->createPreviewEntry('bob', 'Bob'),
			$this->createPreviewEntry('carol', 'Carol'),
			$this->createPreviewEntry('dave', 'Dave'),
		];
		$expected = [
			$contacts[0]->jsonSerialize(),
			$contacts[1]->jsonSerialize(),
			$contacts[2]->jsonSerialize(),
		];

		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->cache->expects($this->once())
			->method('get')
			->with('current-user')
			->willReturn(null);
		$this->contactsManager->expects($this->once())
			->method('getPreviewEntries')
			->with($user, 3)
			->willReturn([$contacts[0], $contacts[1], $contacts[2]]);
		$this->cache->expects($this->once())
			->method('set')
			->with('current-user', $expected, 300);
		$this->teamManager->expects($this->never())
			->method('getMembersOfTeam');

		$this->assertEquals($expected, $this->controller->previewAvatars());
	}

	public function testPreviewAvatarsUsesCache(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('current-user');
		$cached = [
			['uid' => 'alice', 'fullName' => 'Alice', 'isUser' => true],
			['uid' => 'bob', 'fullName' => 'Bob', 'isUser' => true],
		];

		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->cache->expects($this->once())
			->method('get')
			->with('current-user')
			->willReturn($cached);
		$this->contactsManager->expects($this->never())
			->method('getPreviewEntries');
		$this->cache->expects($this->never())
			->method('set');

		$this->assertEquals($cached, $this->controller->previewAvatars());
	}

	public function testPreviewAvatarsWithTeam(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('current-user');

		$cached = [
			['uid' => 'alice', 'fullName' => 'Alice', 'isUser' => true],
			['uid' => 'contact-1', 'fullName' => 'External', 'isUser' => false],
			['uid' => 'bob', 'fullName' => 'Bob', 'isUser' => true],
		];

		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->cache->expects($this->once())
			->method('get')
			->with('current-user')
			->willReturn($cached);
		$this->contactsManager->expects($this->never())
			->method('getPreviewEntries');
		$this->teamManager->expects($this->once())
			->method('getMembersOfTeam')
			->with('team-id', 'current-user')
			->willReturn([
				'alice' => 'Alice',
				'bob' => 'Bob',
				'carol' => 'Carol',
			]);

		$this->assertEquals([
			$cached[0],
			$cached[2],
		], $this->controller->previewAvatars('team-id'));
	}

	public function testPreviewAvatarsWithoutUser(): void {
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn(null);
		$this->contactsManager->expects($this->never())
			->method('getPreviewEntries');
		$this->cache->expects($this->never())
			->method('get');

		$this->assertEquals([], $this->controller->previewAvatars());
	}

	private function createPreviewEntry(string $uid, string $fullName): IEntry&MockObject {
		$entry = $this->createMock(IEntry::class);
		$entry->method('jsonSerialize')->willReturn([
			'uid' => $uid,
			'fullName' => $fullName,
			'isUser' => true,
		]);
		return $entry;
	}
}
