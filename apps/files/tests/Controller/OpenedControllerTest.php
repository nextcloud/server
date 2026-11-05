<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Controller;

use OCA\Files\Controller\OpenedController;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\Node\NodeOpenedEvent;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class OpenedControllerTest extends TestCase {
	private IUserFolder&MockObject $userFolder;
	private IEventDispatcher&MockObject $eventDispatcher;
	private IUser&MockObject $user;
	private OpenedController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('alice');

		$this->userFolder = $this->createMock(IUserFolder::class);
		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->with('alice')->willReturn($this->userFolder);

		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->controller = new OpenedController(
			'files',
			$this->createMock(IRequest::class),
			$rootFolder,
			$this->eventDispatcher,
			$this->createMock(IL10N::class),
		);
	}

	public function testDispatchesEvent(): void {
		$file = $this->createMock(File::class);
		$file->method('isReadable')->willReturn(true);
		$this->userFolder->method('getFirstNodeById')->with(42)->willReturn($file);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (NodeOpenedEvent $event): bool
				=> $event->getNode() === $file && $event->getUser() === $this->user));

		$this->controller->opened($this->user, 42);
	}

	public function testNotFound(): void {
		$this->userFolder->method('getFirstNodeById')->with(42)->willReturn(null);
		$this->eventDispatcher->expects($this->never())->method('dispatchTyped');

		$this->expectException(OCSNotFoundException::class);
		$this->controller->opened($this->user, 42);
	}

	public function testNotReadable(): void {
		$file = $this->createMock(File::class);
		$file->method('isReadable')->willReturn(false);
		$this->userFolder->method('getFirstNodeById')->with(42)->willReturn($file);
		$this->eventDispatcher->expects($this->never())->method('dispatchTyped');

		$this->expectException(OCSNotFoundException::class);
		$this->controller->opened($this->user, 42);
	}
}
