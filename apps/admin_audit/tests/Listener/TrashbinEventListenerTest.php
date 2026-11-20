<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Listener;

use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\TrashbinEventListener;
use OCA\Files_Trashbin\Events\BeforeNodeDeletedEvent;
use OCA\Files_Trashbin\Events\NodeRestoredEvent;
use OCP\EventDispatcher\Event;
use OCP\Files\Node;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TrashbinEventListenerTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private TrashbinEventListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->listener = new TrashbinEventListener($this->logger);
	}

	private function createNode(string $path): Node&MockObject {
		$node = $this->createMock(Node::class);
		$node->method('getPath')->willReturn($path);
		return $node;
	}

	private function expectInfo(string $message, string $operation, array $params): void {
		$this->logger->expects($this->once())
			->method('info')
			->with($message, ['app' => 'admin_audit', 'operation' => $operation, 'params' => $params]);
	}

	public function testDelete(): void {
		$path = '/alice/files_trashbin/files/a.txt.d1700000000';
		$this->expectInfo('File "' . $path . '" deleted from trash bin.', 'trashbin.file.deleted', ['path' => $path]);

		$this->listener->handle(new BeforeNodeDeletedEvent($this->createNode($path)));
	}

	public function testRestore(): void {
		$source = $this->createNode('/alice/files_trashbin/files/a.txt.d1700000000');
		$target = $this->createNode('/alice/files/a.txt');
		$this->expectInfo('File "/alice/files/a.txt" restored from trash bin.', 'trashbin.file.restored', ['path' => '/alice/files/a.txt']);

		$this->listener->handle(new NodeRestoredEvent($source, $target));
	}

	public function testUnrelatedEventIsIgnored(): void {
		$this->logger->expects($this->never())->method('info');

		$this->listener->handle(new Event());
	}
}
