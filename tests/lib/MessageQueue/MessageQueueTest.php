<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Test\MessageQueue;

use OC\MessageQueue\Consumer;
use OC\MessageQueue\HandlerRegistry;
use OC\MessageQueue\MessageBus;
use OC\MessageQueue\MessageMetadataReader;
use OC\MessageQueue\MessageNormalizer;
use OC\MessageQueue\MessageStore;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\ISetupManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ISession;
use OCP\ITempManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Lock\ILockingProvider;
use OCP\MessageQueue\Exception\InvalidMessageException;
use OCP\MessageQueue\Exception\UnrecoverableMessageException;
use OCP\Server;
use OCP\Snowflake\ISnowflakeGenerator;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Test\MessageQueue\Fixtures\FailingMessage;
use Test\MessageQueue\Fixtures\InvokableHandler;
use Test\MessageQueue\Fixtures\LowMessage;
use Test\MessageQueue\Fixtures\MultiHandler;
use Test\MessageQueue\Fixtures\NotAMessage;
use Test\MessageQueue\Fixtures\TestAfterRequestHandler;
use Test\MessageQueue\Fixtures\TestMessage;
use Test\MessageQueue\Fixtures\UserMessage;
use Test\TestCase;

#[Group('DB')]
final class MessageQueueTest extends TestCase {
	private IDBConnection $connection;

	private int $now = 1_800_000_000;

	private ?IUser $activeUser = null;

	private ISetupManager&MockObject $setupManager;

	private InvokableHandler $invokableHandler;

	private MultiHandler $multiHandler;

	private MessageBus $bus;

	private Consumer $consumer;

	private ISession&MockObject $session;

	private ILockingProvider&MockObject $lockingProvider;

	private TestAfterRequestHandler $afterRequestHandler;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->connection = Server::get(IDBConnection::class);
		$this->clearTable();

		$timeFactory = $this->createMock(ITimeFactory::class);
		$timeFactory->method('getTime')->willReturnCallback(fn (): int => $this->now);

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('setVolatileActiveUser')->willReturnCallback(function (?IUser $user): void {
			$this->activeUser = $user;
		});
		$userSession->method('getUser')->willReturnCallback(fn (): ?IUser => $this->activeUser);

		$alice = $this->createMock(IUser::class);
		$alice->method('getUID')->willReturn('alice');
		$userManager = $this->createMock(IUserManager::class);
		$userManager->method('get')->willReturnCallback(static fn (string $uid) => $uid === 'alice' ? $alice : null);

		$this->setupManager = $this->createMock(ISetupManager::class);
		$this->invokableHandler = new InvokableHandler();
		$this->multiHandler = new MultiHandler($userSession);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnMap([
			[InvokableHandler::class, $this->invokableHandler],
			[MultiHandler::class, $this->multiHandler],
		]);

		$handlerRegistry = $this->createMock(HandlerRegistry::class);
		$handlerRegistry->method('getHandler')->willReturnCallback(static fn (string $class): ?array => match ($class) {
			TestMessage::class => ['class' => InvokableHandler::class, 'method' => '__invoke'],
			UserMessage::class => ['class' => MultiHandler::class, 'method' => 'user'],
			FailingMessage::class => ['class' => MultiHandler::class, 'method' => 'fail'],
			LowMessage::class => ['class' => MultiHandler::class, 'method' => 'low'],
			default => null,
		});

		$logger = new NullLogger();
		$metadataReader = new MessageMetadataReader();
		$normalizer = new MessageNormalizer($metadataReader);
		$store = new MessageStore($this->connection, $timeFactory, Server::get(ISnowflakeGenerator::class));
		$this->consumer = new Consumer(
			$store,
			$handlerRegistry,
			$metadataReader,
			$normalizer,
			$container,
			$userManager,
			$userSession,
			$this->setupManager,
			$this->connection,
			$this->createMock(ITempManager::class),
			$logger,
		);
		$this->session = $this->createMock(ISession::class);
		$this->lockingProvider = $this->createMock(ILockingProvider::class);
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueBool')->willReturn(true);
		$this->afterRequestHandler = new TestAfterRequestHandler($this->consumer, $config, $this->session, $userSession, $this->setupManager, $this->lockingProvider, $logger, false);
		$this->bus = new MessageBus($store, $handlerRegistry, $metadataReader, $normalizer, $this->afterRequestHandler);
	}

	#[\Override]
	protected function tearDown(): void {
		$this->clearTable();
		parent::tearDown();
	}

	private function clearTable(): void {
		$this->connection->getQueryBuilder()->delete(MessageStore::TABLE)->executeStatement();
	}

	/**
	 * @return list<array{queue_name: string, available_at: int, retry_count: int, last_error: ?string}>
	 */
	private function getRows(): array {
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select('queue_name', 'available_at', 'retry_count', 'last_error')
			->from(MessageStore::TABLE)
			->orderBy('id')
			->executeQuery();
		$rows = array_map(static fn (array $row): array => [
			'queue_name' => (string)$row['queue_name'],
			'available_at' => (int)$row['available_at'],
			'retry_count' => (int)$row['retry_count'],
			'last_error' => $row['last_error'] === null ? null : (string)$row['last_error'],
		], $result->fetchAllAssociative());
		$result->closeCursor();
		return $rows;
	}

	private function consume(): void {
		$this->consumer->consume(stopWhenEmpty: true);
	}

	public function testDispatchAndConsume(): void {
		$message = new TestMessage(id: 1, text: 'hello');
		$this->bus->dispatch($message);

		$this->assertSame('high', $this->getRows()[0]['queue_name']);
		$this->assertSame([], $this->invokableHandler->handled);

		$this->consume();

		$this->assertEquals([$message], $this->invokableHandler->handled);
		$this->assertSame([], $this->getRows());
	}

	public function testDispatchRejectsMessageWithoutHandler(): void {
		$this->expectException(InvalidMessageException::class);
		$this->bus->dispatch(new NotAMessage(1));
	}

	public function testDispatchRejectsUnsupportedValues(): void {
		$this->expectException(InvalidMessageException::class);
		$this->bus->dispatch(new TestMessage(id: 1, list: [new \stdClass()]));
	}

	public function testDeduplication(): void {
		$this->bus->dispatch(new TestMessage(id: 1, text: 'first'));
		$this->bus->dispatch(new TestMessage(id: 1, text: 'second'));
		$this->bus->dispatch(new TestMessage(id: 2));
		$this->assertCount(2, $this->getRows());

		$this->consume();
		$this->assertSame(['first', 'default'], array_map(static fn (TestMessage $m): string => $m->text, $this->invokableHandler->handled));

		$this->bus->dispatch(new TestMessage(id: 1, text: 'third'));
		$this->assertCount(1, $this->getRows());
	}

	public function testUserScope(): void {
		$this->setupManager->expects($this->once())->method('setupForUser');
		$this->setupManager->expects($this->once())->method('tearDown');

		$this->bus->dispatch(new UserMessage('alice'));
		$this->consume();

		$this->assertSame(['alice'], $this->multiHandler->users);
		$this->assertNull($this->activeUser);
	}

	public function testUnknownUserFailsWithoutRetry(): void {
		$this->bus->dispatch(new UserMessage('bob'));
		$this->consume();

		$this->assertSame([], $this->multiHandler->users);
		$rows = $this->getRows();
		$this->assertCount(1, $rows);
		$this->assertSame('failed', $rows[0]['queue_name']);
	}

	public function testRetryThenFailureQueue(): void {
		$this->bus->dispatch(new FailingMessage());
		$this->consume();

		$rows = $this->getRows();
		$this->assertCount(1, $rows);
		$this->assertSame('default', $rows[0]['queue_name']);
		$this->assertSame(1, $rows[0]['retry_count']);
		$this->assertSame($this->now + 30, $rows[0]['available_at']);

		$this->now += 30;
		$this->consume();

		$rows = $this->getRows();
		$this->assertCount(1, $rows);
		$this->assertSame('failed', $rows[0]['queue_name']);
		$this->assertSame('RuntimeException: Temporary failure', $rows[0]['last_error']);
	}

	public function testRecoverableIsRetriedWithBackoff(): void {
		$this->bus->dispatch(new FailingMessage(recoverable: true));

		foreach ([30, 60, 120] as $retry => $delay) {
			$this->consume();
			$rows = $this->getRows();
			$this->assertSame('default', $rows[0]['queue_name']);
			$this->assertSame($retry + 1, $rows[0]['retry_count']);
			$this->assertSame($this->now + $delay, $rows[0]['available_at']);
			$this->now += $delay;
		}
	}

	public function testUnrecoverableGoesToFailureQueue(): void {
		$this->bus->dispatch(new FailingMessage(unrecoverable: true));
		$this->consume();

		$rows = $this->getRows();
		$this->assertCount(1, $rows);
		$this->assertSame('failed', $rows[0]['queue_name']);
		$this->assertSame(UnrecoverableMessageException::class . ': Permanent failure', $rows[0]['last_error']);
	}

	public function testUndecodableMessageIsMovedToFailureQueue(): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->insert(MessageStore::TABLE)
			->values([
				'id' => $qb->createNamedParameter(Server::get(ISnowflakeGenerator::class)->nextId(), IQueryBuilder::PARAM_INT),
				'queue_name' => $qb->createNamedParameter('default'),
				'message_class' => $qb->createNamedParameter(NotAMessage::class),
				'body' => $qb->createNamedParameter('{"id":1}'),
				'created_at' => $qb->createNamedParameter($this->now, IQueryBuilder::PARAM_INT),
				'available_at' => $qb->createNamedParameter($this->now, IQueryBuilder::PARAM_INT),
			])
			->executeStatement();

		$this->consume();

		$rows = $this->getRows();
		$this->assertCount(1, $rows);
		$this->assertSame('failed', $rows[0]['queue_name']);
	}

	public function testHandleAfterRequest(): void {
		$this->session->expects($this->once())->method('close');
		$this->lockingProvider->expects($this->once())->method('releaseAll');

		$this->bus->dispatch(new TestMessage(1));
		$this->bus->dispatch(new UserMessage('alice'));
		$this->assertCount(1, $this->afterRequestHandler->shutdownFunctions);

		$this->afterRequestHandler->run();

		$this->assertTrue($this->afterRequestHandler->requestFinished);
		$this->assertCount(1, $this->invokableHandler->handled);
		$this->assertSame(['alice'], $this->multiHandler->users);
		$this->assertSame([], $this->getRows());
	}

	public function testHandleAfterRequestSkipsLowMessages(): void {
		$this->bus->dispatch(new LowMessage(1));
		$this->assertSame([], $this->afterRequestHandler->shutdownFunctions);

		$this->afterRequestHandler->run();

		$this->assertFalse($this->afterRequestHandler->requestFinished);
		$this->assertCount(1, $this->getRows());
	}

	public function testHandleAfterRequestRespectsMessageLimit(): void {
		for ($i = 1; $i <= 7; ++$i) {
			$this->bus->dispatch(new TestMessage($i));
		}

		$this->afterRequestHandler->run();

		$this->assertSame([1, 2, 3, 4, 5], array_map(static fn (TestMessage $m): int => $m->id, $this->invokableHandler->handled));
		$this->assertCount(2, $this->getRows());
	}

	public function testHandleAfterRequestOnlyHandlesOwnMessages(): void {
		$this->bus->dispatch(new TestMessage(1));
		$this->consume();
		$this->bus->dispatch(new TestMessage(2));

		$this->afterRequestHandler->run();

		$this->assertSame([1, 2], array_map(static fn (TestMessage $m): int => $m->id, $this->invokableHandler->handled));
	}

	public function testHandleAfterRequestRetriesFailures(): void {
		$this->bus->dispatch(new FailingMessage());

		$this->afterRequestHandler->run();

		$rows = $this->getRows();
		$this->assertCount(1, $rows);
		$this->assertSame('default', $rows[0]['queue_name']);
		$this->assertSame(1, $rows[0]['retry_count']);
	}
}
