<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Listener;

use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\SharingEventListener;
use OCP\Files\File;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SharingEventListenerTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private SharingEventListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->listener = new SharingEventListener($this->logger);
	}

	private function createShare(int $shareType): IShare&MockObject {
		$node = $this->createMock(File::class);
		$node->method('getPath')->willReturn('/alice/files/a.txt');

		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn($shareType);
		$share->method('getNodeType')->willReturn('file');
		$share->method('getNode')->willReturn($node);
		$share->method('getNodeId')->willReturn(42);
		$share->method('getSharedWith')->willReturn('bob');
		$share->method('getPermissions')->willReturn(19);
		$share->method('getId')->willReturn('7');
		$share->method('getTarget')->willReturn('/a.txt');
		return $share;
	}

	public static function shareTypeProvider(): array {
		return [
			'link' => [IShare::TYPE_LINK],
			'user' => [IShare::TYPE_USER],
			'group' => [IShare::TYPE_GROUP],
			'room' => [IShare::TYPE_ROOM],
			'email' => [IShare::TYPE_EMAIL],
			'circle' => [IShare::TYPE_CIRCLE],
			'remote' => [IShare::TYPE_REMOTE],
			'remote group' => [IShare::TYPE_REMOTE_GROUP],
			'deck' => [IShare::TYPE_DECK],
		];
	}

	#[DataProvider('shareTypeProvider')]
	public function testShareCreatedOperation(int $shareType): void {
		$params = ['itemType' => 'file', 'path' => '/alice/files/a.txt', 'itemSource' => 42, 'shareWith' => 'bob', 'permissions' => 19, 'id' => '7'];
		if ($shareType === IShare::TYPE_LINK) {
			unset($params['shareWith']);
		}
		$this->logger->expects($this->once())
			->method('info')
			->with($this->isString(), ['app' => 'admin_audit', 'operation' => 'sharing.share.created', 'params' => $params]);

		$this->listener->handle(new ShareCreatedEvent($this->createShare($shareType)));
	}

	#[DataProvider('shareTypeProvider')]
	public function testShareDeletedOperation(int $shareType): void {
		$params = ['itemType' => 'file', 'fileTarget' => '/a.txt', 'itemSource' => 42, 'shareWith' => 'bob', 'id' => '7'];
		if ($shareType === IShare::TYPE_LINK) {
			unset($params['shareWith']);
		}
		$this->logger->expects($this->once())
			->method('info')
			->with($this->isString(), ['app' => 'admin_audit', 'operation' => 'sharing.share.deleted', 'params' => $params]);

		$this->listener->handle(new ShareDeletedEvent($this->createShare($shareType)));
	}

	public function testUserShareCreatedMessage(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				'The file "/alice/files/a.txt" with ID "42" has been shared to the user "bob" with permissions "19"  (Share ID: 7)',
				['app' => 'admin_audit', 'operation' => 'sharing.share.created', 'params' => ['itemType' => 'file', 'path' => '/alice/files/a.txt', 'itemSource' => 42, 'shareWith' => 'bob', 'permissions' => 19, 'id' => '7']],
			);

		$this->listener->handle(new ShareCreatedEvent($this->createShare(IShare::TYPE_USER)));
	}

	public function testLinkShareDeletedMessage(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				'The file "/a.txt" with ID "42" has been unshared (Share ID: 7)',
				['app' => 'admin_audit', 'operation' => 'sharing.share.deleted', 'params' => ['itemType' => 'file', 'fileTarget' => '/a.txt', 'itemSource' => 42, 'id' => '7']],
			);

		$this->listener->handle(new ShareDeletedEvent($this->createShare(IShare::TYPE_LINK)));
	}
}
