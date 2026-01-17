<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Tests\Db;

use OCA\WebhookListeners\Db\AuthMethod;
use OCA\WebhookListeners\Db\WebhookListenerMapper;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\Server;
use OCP\User\Events\UserCreatedEvent;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class WebhookListenerMapperTest extends TestCase {
	private IDBConnection $connection;
	private WebhookListenerMapper $mapper;
	private ICacheFactory $cacheFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->cacheFactory = Server::get(ICacheFactory::class);
		$this->pruneTables();

		$this->mapper = new WebhookListenerMapper(
			$this->connection,
			$this->cacheFactory,
		);
	}

	protected function tearDown(): void {
		$this->pruneTables();
		parent::tearDown();
	}

	protected function pruneTables(): void {
		$query = $this->connection->getQueryBuilder();
		$query->delete(WebhookListenerMapper::TABLE_NAME)->executeStatement();
	}

	public function testInsertListenerWithNotSupportedEvent(): void {
		$this->expectException(\UnexpectedValueException::class);
		$listener1 = $this->mapper->addWebhookListener(
			null,
			'bob',
			'POST',
			'https://webhook.example.com/endpoint',
			UserCreatedEvent::class,
			null,
			null,
			null,
			AuthMethod::None,
			null,
		);
	}

	public function testInsertListenerAndGetIt(): void {
		$listener1 = $this->mapper->addWebhookListener(
			null,
			'bob',
			'POST',
			'https://webhook.example.com/endpoint',
			NodeWrittenEvent::class,
			null,
			null,
			null,
			AuthMethod::None,
			null,
		);

		$listener2 = $this->mapper->getById($listener1->getId());

		$listener1->resetUpdatedFields();
		$this->assertEquals($listener1, $listener2);
	}

	public function testInsertListenerAndGetItByUri(): void {
		$uri = 'https://webhook.example.com/endpoint';
		$listener1 = $this->mapper->addWebhookListener(
			null,
			'bob',
			'POST',
			$uri,
			NodeWrittenEvent::class,
			null,
			null,
			null,
			AuthMethod::None,
			null,
		);

		$listeners = $this->mapper->getByUri($uri);

		$listener1->resetUpdatedFields();
		$this->assertContains($listener1->getId(), array_map(fn ($listener) => $listener->getId(), $listeners));
	}

	public function testInsertListenerAndGetItWithAuthData(): void {
		$listener1 = $this->mapper->addWebhookListener(
			null,
			'bob',
			'POST',
			'https://webhook.example.com/endpoint',
			NodeWrittenEvent::class,
			null,
			null,
			null,
			AuthMethod::Header,
			['secretHeader' => 'header'],
		);

		$listener2 = $this->mapper->getById($listener1->getId());

		$listener1->resetUpdatedFields();
		$this->assertEquals($listener1, $listener2);
	}

	public function testInsertListenerAndGetItByEventAndUser(): void {
		$listener1 = $this->mapper->addWebhookListener(
			null,
			'bob',
			'POST',
			'https://webhook.example.com/endpoint',
			NodeWrittenEvent::class,
			null,
			'alice',
			null,
			AuthMethod::None,
			null,
		);
		$listener1->resetUpdatedFields();

		$this->assertEquals([NodeWrittenEvent::class], $this->mapper->getAllConfiguredEvents('alice'));
		$this->assertEquals([], $this->mapper->getAllConfiguredEvents(''));
		$this->assertEquals([], $this->mapper->getAllConfiguredEvents('otherUser'));

		$this->assertEquals([$listener1], $this->mapper->getByEvent(NodeWrittenEvent::class, 'alice'));
		$this->assertEquals([], $this->mapper->getByEvent(NodeWrittenEvent::class, ''));
		$this->assertEquals([], $this->mapper->getByEvent(NodeWrittenEvent::class, 'otherUser'));

		/* Add a second listener with no user filter */
		$listener2 = $this->mapper->addWebhookListener(
			null,
			'bob',
			'POST',
			'https://webhook.example.com/endpoint',
			NodeWrittenEvent::class,
			null,
			'',
			null,
			AuthMethod::None,
			null,
		);
		$listener2->resetUpdatedFields();

		$this->assertEquals([NodeWrittenEvent::class], $this->mapper->getAllConfiguredEvents('alice'));
		$this->assertEquals([NodeWrittenEvent::class], $this->mapper->getAllConfiguredEvents(''));

		$this->assertEquals([$listener1, $listener2], $this->mapper->getByEvent(NodeWrittenEvent::class, 'alice'));
		$this->assertEquals([$listener2], $this->mapper->getByEvent(NodeWrittenEvent::class, 'otherUser'));
		$this->assertEquals([$listener2], $this->mapper->getByEvent(NodeWrittenEvent::class));
	}
}
