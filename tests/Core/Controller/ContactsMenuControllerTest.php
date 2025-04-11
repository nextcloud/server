<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Controller;

use OC\Contacts\ContactsMenu\Manager;
use OC\Core\Controller\ContactsMenuController;
use OCP\Contacts\ContactsMenu\IEntry;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ContactsMenuControllerTest extends TestCase {
	/** @var IUserSession|MockObject */
	private $userSession;

	/** @var Manager|MockObject */
	private $contactsManager;

	private ContactsMenuController $controller;

	protected function setUp(): void {
		parent::setUp();

		$request = $this->createMock(IRequest::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->contactsManager = $this->createMock(Manager::class);

		$this->controller = new ContactsMenuController($request, $this->userSession, $this->contactsManager);
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
