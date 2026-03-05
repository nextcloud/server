<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Controller;

use OC\Contacts\ContactsMenu\Manager;
use OC\Core\Controller\ContactsMenuController;
use OC\Teams\TeamManager;
use OCP\Contacts\ContactsMenu\IEntry;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ContactsMenuControllerTest extends TestCase {
	private IUserSession&MockObject $userSession;
	private Manager&MockObject $contactsManager;
	private TeamManager&MockObject $teamManager;

	private ContactsMenuController $controller;

	protected function setUp(): void {
		parent::setUp();

		$request = $this->createMock(IRequest::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->contactsManager = $this->createMock(Manager::class);
		$this->teamManager = $this->createMock(TeamManager::class);

		$this->controller = new ContactsMenuController(
			$request,
			$this->userSession,
			$this->contactsManager,
			$this->teamManager,
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
		$entries[0]->method('getProperty')
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
			->willReturn(['member1', 'member3']);

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
}
