<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Test\MessageQueue;

use OC\Memcache\ArrayCache;
use OC\MessageQueue\HandlerRegistry;
use OC\MessageQueue\MessageMetadataReader;
use OCP\App\IAppManager;
use OCP\ICacheFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\MessageQueue\Fixtures\DuplicateHandler;
use Test\MessageQueue\Fixtures\FailingMessage;
use Test\MessageQueue\Fixtures\InvokableHandler;
use Test\MessageQueue\Fixtures\LowMessage;
use Test\MessageQueue\Fixtures\MultiHandler;
use Test\MessageQueue\Fixtures\NotAMessage;
use Test\MessageQueue\Fixtures\TestMessage;
use Test\MessageQueue\Fixtures\UserMessage;
use Test\TestCase;

final class HandlerRegistryTest extends TestCase {
	private IAppManager&MockObject $appManager;

	private LoggerInterface&MockObject $logger;

	private ArrayCache $cache;

	private HandlerRegistry $registry;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->appManager = $this->createMock(IAppManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->cache = new ArrayCache();
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createLocal')->willReturn($this->cache);

		$this->appManager->method('getEnabledApps')->willReturn(['app1', 'app2']);
		$this->appManager->method('getAppVersion')->willReturn('1.0.0');
		$this->appManager->method('getAppInfo')->willReturnMap([
			['app1', false, null, ['message-handlers' => [InvokableHandler::class, MultiHandler::class]]],
			['app2', false, null, ['message-handlers' => ['handler' => DuplicateHandler::class]]],
		]);

		$this->registry = new HandlerRegistry($this->appManager, new MessageMetadataReader(), $this->logger, $cacheFactory);
	}

	public function testCollectsHandlers(): void {
		$this->logger->expects($this->exactly(2))->method('error');

		$this->assertSame([
			TestMessage::class => ['class' => InvokableHandler::class, 'method' => '__invoke'],
			UserMessage::class => ['class' => MultiHandler::class, 'method' => 'user'],
			FailingMessage::class => ['class' => MultiHandler::class, 'method' => 'fail'],
			LowMessage::class => ['class' => MultiHandler::class, 'method' => 'low'],
		], $this->registry->getHandlers());
		$this->assertNull($this->registry->getHandler(NotAMessage::class));
	}

	public function testUsesCachedHandlers(): void {
		$cached = [TestMessage::class => ['class' => InvokableHandler::class, 'method' => '__invoke']];
		$this->cache->set('handlers-' . md5(json_encode(['app1' => '1.0.0', 'app2' => '1.0.0'], JSON_THROW_ON_ERROR)), $cached);
		$this->appManager->expects($this->never())->method('getAppInfo');

		$this->assertSame($cached, $this->registry->getHandlers());
	}

	public function testStoresHandlersInCache(): void {
		$handlers = $this->registry->getHandlers();
		$this->assertSame($handlers, $this->cache->get('handlers-' . md5(json_encode(['app1' => '1.0.0', 'app2' => '1.0.0'], JSON_THROW_ON_ERROR))));
	}
}
