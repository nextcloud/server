<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Test\MessageQueue;

use OC\MessageQueue\Consumer;
use OCP\Files\ISetupManager;
use OCP\IConfig;
use OCP\ISession;
use OCP\IUserSession;
use OCP\Lock\ILockingProvider;
use OCP\MessageQueue\Queue;
use Psr\Log\LoggerInterface;
use Test\MessageQueue\Fixtures\TestAfterRequestHandler;
use Test\TestCase;

final class AfterRequestHandlerTest extends TestCase {
	private function createHandler(
		Consumer $consumer,
		bool $enabled = true,
		bool $isCLI = false,
		?ILockingProvider $lockingProvider = null,
		?LoggerInterface $logger = null,
	): TestAfterRequestHandler {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueBool')->with('message_queue.handle_after_request', true)->willReturn($enabled);

		return new TestAfterRequestHandler(
			$consumer,
			$config,
			$this->createMock(ISession::class),
			$this->createMock(IUserSession::class),
			$this->createMock(ISetupManager::class),
			$lockingProvider ?? $this->createMock(ILockingProvider::class),
			$logger ?? $this->createMock(LoggerInterface::class),
			$isCLI,
		);
	}

	public function testEnabled(): void {
		$consumer = $this->createMock(Consumer::class);
		$consumer->expects($this->once())->method('consumeMessages')->with(['1', '2', '3'], 10, 5);

		$handler = $this->createHandler($consumer);
		$handler->add(Queue::High, '1');
		$handler->add(Queue::Default, '2');
		$handler->add(Queue::High, '3');
		$handler->add(Queue::Low, '4');
		$this->assertCount(1, $handler->shutdownFunctions);

		$handler->run();
		$handler->run();
		$this->assertTrue($handler->requestFinished);
	}

	public function testDisabledOnCli(): void {
		$consumer = $this->createMock(Consumer::class);
		$consumer->expects($this->never())->method('consumeMessages');

		$handler = $this->createHandler($consumer, isCLI: true);
		$handler->add(Queue::High, '1');
		$handler->run();
		$this->assertSame([], $handler->shutdownFunctions);
	}

	public function testDisabledByConfig(): void {
		$consumer = $this->createMock(Consumer::class);
		$consumer->expects($this->never())->method('consumeMessages');

		$handler = $this->createHandler($consumer, enabled: false);
		$handler->add(Queue::High, '1');
		$handler->run();
		$this->assertSame([], $handler->shutdownFunctions);
	}

	public function testErrorsAreLoggedAndLocksReleased(): void {
		$consumer = $this->createMock(Consumer::class);
		$consumer->method('consumeMessages')->willThrowException(new \RuntimeException('Broken'));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error');
		$lockingProvider = $this->createMock(ILockingProvider::class);
		$lockingProvider->expects($this->once())->method('releaseAll');

		$handler = $this->createHandler($consumer, lockingProvider: $lockingProvider, logger: $logger);
		$handler->add(Queue::High, '1');
		$handler->run();
	}
}
