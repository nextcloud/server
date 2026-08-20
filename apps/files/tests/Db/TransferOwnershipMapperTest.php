<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Db;

use OCA\Files\Db\TransferOwnership;
use OCA\Files\Db\TransferOwnershipMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IDBConnection;
use OCP\Server;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class TransferOwnershipMapperTest extends TestCase {
	private IDBConnection $db;
	private TransferOwnershipMapper $mapper;
	private string $sourceUser = 'test123456';

	private function resetDB(): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->mapper->getTableName())
			->where($qb->expr()->eq('source_user', $qb->createNamedParameter($this->sourceUser)));
		$qb->executeStatement();
	}

	protected function setUp(): void {
		parent::setUp();

		$this->db = Server::get(IDBConnection::class);
		$this->mapper = Server::get(TransferOwnershipMapper::class);

		$this->resetDB();
	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->resetDB();
	}

	public function testInsertAndGetById(): void {
		$entity = new TransferOwnership();
		$entity->sourceUser = $this->sourceUser;
		$entity->targetUser = 'recipient123456';
		$entity->fileId = 42;
		$entity->nodeName = 'welcome.txt';

		$inserted = $this->mapper->insert($entity);

		$found = $this->mapper->getById($inserted->id);

		$this->assertSame($inserted->id, $found->id);
		$this->assertSame($this->sourceUser, $found->sourceUser);
		$this->assertSame('recipient123456', $found->targetUser);
		$this->assertSame(42, $found->fileId);
		$this->assertSame('welcome.txt', $found->nodeName);
	}

	public function testGetByIdNotFound(): void {
		$entity = new TransferOwnership();
		$entity->sourceUser = $this->sourceUser;
		$entity->targetUser = 'recipient123456';
		$entity->fileId = 42;
		$entity->nodeName = 'welcome.txt';

		$inserted = $this->mapper->insert($entity);
		$missingId = $inserted->id + 1000000;

		$this->mapper->delete($inserted);

		$this->expectException(DoesNotExistException::class);
		$this->mapper->getById($missingId);
	}

	public function testDelete(): void {
		$entity = new TransferOwnership();
		$entity->sourceUser = $this->sourceUser;
		$entity->targetUser = 'recipient123456';
		$entity->fileId = 42;
		$entity->nodeName = 'welcome.txt';

		$inserted = $this->mapper->insert($entity);
		$this->mapper->delete($inserted);

		$this->expectException(DoesNotExistException::class);
		$this->mapper->getById($inserted->id);
	}
}
