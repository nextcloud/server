<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests\Controller;

use OCA\Files_Sharing\Controller\DeletedShareAPIController;
use OCP\App\IAppManager;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Share\Exceptions\GenericShareException;
use OCP\Share\IManager as ShareManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class DeletedShareAPIControllerTest extends TestCase {
	private ShareManager&MockObject $shareManager;
	private IEventDispatcher&MockObject $eventDispatcher;
	private DeletedShareAPIController $controller;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->shareManager = $this->createMock(ShareManager::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->controller = new DeletedShareAPIController(
			'files_sharing',
			$this->createMock(IRequest::class),
			$this->shareManager,
			'bob',
			$this->createMock(IUserManager::class),
			$this->createMock(IGroupManager::class),
			$this->createMock(IRootFolder::class),
			$this->createMock(IAppManager::class),
			$this->eventDispatcher,
		);
	}

	private function createDeletedShare(int $permissions = 0): IShare&MockObject {
		$share = $this->createMock(IShare::class);
		$share->method('getPermissions')->willReturn($permissions);
		$share->method('getNodeType')->willReturn('folder');
		$share->method('getNodeId')->willReturn(42);
		$share->method('getSharedWith')->willReturn('staff');
		$share->method('getId')->willReturn('7');
		$share->method('getShareType')->willReturn(IShare::TYPE_GROUP);
		return $share;
	}

	public function testUndeleteLogsRestore(): void {
		$share = $this->createDeletedShare();
		$this->shareManager->method('getShareById')->with('ocinternal:7', 'bob')->willReturn($share);
		$this->shareManager->expects($this->once())->method('restoreShare')->with($share, 'bob');

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool
				=> $event->getOperation() === 'sharing.share.restored'
				&& $event->getLogMessage() === 'The share of the %s with ID "%s" for "%s" was restored (Share ID: %s)'
				&& $event->getParameters() === [
					'itemType' => 'folder',
					'itemSource' => 42,
					'shareWith' => 'staff',
					'id' => '7',
					'shareType' => IShare::TYPE_GROUP,
				]));

		$this->controller->undelete('ocinternal:7');
	}

	public function testUndeleteOfActiveShareDoesNotLog(): void {
		$this->shareManager->method('getShareById')->willReturn($this->createDeletedShare(1));
		$this->eventDispatcher->expects($this->never())->method('dispatchTyped');

		$this->expectException(OCSNotFoundException::class);
		$this->controller->undelete('ocinternal:7');
	}

	public function testUndeleteFailureDoesNotLog(): void {
		$this->shareManager->method('getShareById')->willReturn($this->createDeletedShare());
		$this->shareManager->method('restoreShare')->willThrowException(new GenericShareException());
		$this->eventDispatcher->expects($this->never())->method('dispatchTyped');

		$this->expectException(OCSException::class);
		$this->controller->undelete('ocinternal:7');
	}
}
