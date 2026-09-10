<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Db;

use OCA\Files\Db\OpenLocalEditor;
use OCA\Files\Db\OpenLocalEditorMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IDBConnection;
use OCP\Server;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
final class OpenLocalEditorMapperTest extends TestCase {
	private IDBConnection $db;

	private OpenLocalEditorMapper $mapper;

	private string $testUID = 'test123456';

	private function resetDB(): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->mapper->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($this->testUID)));
		$qb->executeStatement();
	}

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->db = Server::get(IDBConnection::class);
		$this->mapper = Server::get(OpenLocalEditorMapper::class);

		$this->resetDB();
	}

	#[\Override]
	protected function tearDown(): void {
		parent::tearDown();

		$this->resetDB();
	}

	private function createEntry(string $pathHash, string $token, int $expirationTime): OpenLocalEditor {
		$entity = new OpenLocalEditor();
		$entity->userId = $this->testUID;
		$entity->pathHash = $pathHash;
		$entity->token = $token;
		$entity->expirationTime = $expirationTime;

		return $this->mapper->insert($entity);
	}

	public function testVerifyToken(): void {
		$inserted = $this->createEntry('pathHash', 'thetoken', 1000);

		$found = $this->mapper->verifyToken($this->testUID, 'pathHash', 'thetoken');

		$this->assertSame($inserted->id, $found->id);
		$this->assertSame($this->testUID, $found->userId);
		$this->assertSame('pathHash', $found->pathHash);
		$this->assertSame('thetoken', $found->token);
		$this->assertSame(1000, $found->expirationTime);
	}

	public function testVerifyTokenNotFound(): void {
		$this->createEntry('pathHash', 'thetoken', 1000);

		$this->expectException(DoesNotExistException::class);
		$this->mapper->verifyToken($this->testUID, 'pathHash', 'wrongtoken');
	}

	public function testDeleteExpiredTokens(): void {
		$this->createEntry('expired', 'expiredtoken', 1000);
		$this->createEntry('valid', 'validtoken', 3000);

		$this->mapper->deleteExpiredTokens(2000);

		$this->expectException(DoesNotExistException::class);
		$this->mapper->verifyToken($this->testUID, 'expired', 'expiredtoken');
	}

	public function testDeleteExpiredTokensKeepsUnexpired(): void {
		$this->createEntry('expired', 'expiredtoken', 1000);
		$valid = $this->createEntry('valid', 'validtoken', 3000);

		$this->mapper->deleteExpiredTokens(2000);

		$found = $this->mapper->verifyToken($this->testUID, 'valid', 'validtoken');
		$this->assertSame($valid->id, $found->id);
	}
}
