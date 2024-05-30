<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Webhooks\Tests\Db;

use OCA\Webhooks\Db\WebhookListenerMapper;
use OCP\IDBConnection;
use OCP\User\Events\UserCreatedEvent;
use Test\TestCase;

/**
 * @group DB
 */
class WebhookListenerMapperTest extends TestCase {
	private IDBConnection $connection;
	private WebhookListenerMapper $mapper;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = \OCP\Server::get(IDBConnection::class);
		$this->pruneTables();

		$this->mapper = new WebhookListenerMapper(
			$this->connection,
		);
	}

	protected function tearDown(): void {
		$this->pruneTables();
		parent::tearDown();
	}

	protected function pruneTables() {
		$query = $this->connection->getQueryBuilder();
		$query->delete(WebhookListenerMapper::TABLE_NAME)->executeStatement();
	}

	public function testInsertListenerAndGetIt() {
		$listener1 = $this->mapper->addWebhookListener(
			'bob',
			'POST',
			'https://webhook.example.com/endpoint',
			UserCreatedEvent::class,
			null,
			null,
			null,
			null,
		);

		$listener2 = $this->mapper->getById($listener1->getId());

		$listener1->resetUpdatedFields();
		$this->assertEquals($listener1, $listener2);
	}
}
