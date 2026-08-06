<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests\Command;

use OCA\Files_Sharing\Command\FixOwncloudGroupSubshareStatus;
use OCA\Files_Sharing\Tests\TestCase;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Server;
use OCP\Share\IShare;
use Symfony\Component\Console\Tester\CommandTester;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class FixOwncloudGroupSubshareStatusTest extends TestCase {

	private IDBConnection $connection;
	private CommandTester $commandTester;

	protected function setUp(): void {
		parent::setUp();
		$this->connection = Server::get(IDBConnection::class);
		$this->commandTester = new CommandTester(new FixOwncloudGroupSubshareStatus($this->connection));
		$this->cleanDB();
	}

	protected function tearDown(): void {
		$this->cleanDB();
		parent::tearDown();
	}

	private function cleanDB(): void {
		$this->connection->getQueryBuilder()->delete('share')->executeStatement();
	}

	private function insertShare(int $shareType, int $accepted, int $permissions): int {
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type' => $qb->createNamedParameter($shareType, IQueryBuilder::PARAM_INT),
				'share_with' => $qb->createNamedParameter('user1'),
				'uid_owner' => $qb->createNamedParameter('owner'),
				'uid_initiator' => $qb->createNamedParameter('owner'),
				'parent' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
				'item_type' => $qb->createNamedParameter('file'),
				'item_source' => $qb->createNamedParameter('42'),
				'item_target' => $qb->createNamedParameter('/file'),
				'file_source' => $qb->createNamedParameter(42, IQueryBuilder::PARAM_INT),
				'file_target' => $qb->createNamedParameter('/file'),
				'permissions' => $qb->createNamedParameter($permissions, IQueryBuilder::PARAM_INT),
				'stime' => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
				'accepted' => $qb->createNamedParameter($accepted, IQueryBuilder::PARAM_INT),
			])
			->executeStatement();
		return (int)$this->connection->lastInsertId('*PREFIX*share');
	}

	private function getAccepted(int $id): int {
		$qb = $this->connection->getQueryBuilder();
		return (int)$qb->select('accepted')
			->from('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->executeQuery()
			->fetchOne();
	}

	public function testFixesPendingSubshareWithPermissions(): void {
		$id = $this->insertShare(IShare::TYPE_USERGROUP, IShare::STATUS_PENDING, 31);

		$this->commandTester->execute([]);

		$this->assertSame(IShare::STATUS_ACCEPTED, $this->getAccepted($id));
		$this->assertStringContainsString('Fixed', $this->commandTester->getDisplay());
	}

	public function testDryRunShowsCountWithoutChanging(): void {
		$id = $this->insertShare(IShare::TYPE_USERGROUP, IShare::STATUS_PENDING, 31);

		$this->commandTester->execute(['--dry-run' => true]);

		$this->assertSame(IShare::STATUS_PENDING, $this->getAccepted($id));
		$this->assertStringContainsString('dry-run', $this->commandTester->getDisplay());
	}

	public function testDoesNotTouchDeclinedSubshare(): void {
		// permissions = 0 means the user explicitly declined the share
		$id = $this->insertShare(IShare::TYPE_USERGROUP, IShare::STATUS_PENDING, 0);

		$this->commandTester->execute([]);

		$this->assertSame(IShare::STATUS_PENDING, $this->getAccepted($id));
		$this->assertStringContainsString('No affected', $this->commandTester->getDisplay());
	}

	public function testDoesNotTouchAlreadyAcceptedSubshare(): void {
		$id = $this->insertShare(IShare::TYPE_USERGROUP, IShare::STATUS_ACCEPTED, 31);

		$this->commandTester->execute([]);

		$this->assertSame(IShare::STATUS_ACCEPTED, $this->getAccepted($id));
		$this->assertStringContainsString('No affected', $this->commandTester->getDisplay());
	}

	public function testDoesNotTouchNonUsergroupShares(): void {
		$id = $this->insertShare(IShare::TYPE_GROUP, IShare::STATUS_PENDING, 31);

		$this->commandTester->execute([]);

		$this->assertSame(IShare::STATUS_PENDING, $this->getAccepted($id));
		$this->assertStringContainsString('No affected', $this->commandTester->getDisplay());
	}

	public function testFixesMultipleAffectedRows(): void {
		$id1 = $this->insertShare(IShare::TYPE_USERGROUP, IShare::STATUS_PENDING, 31);
		$id2 = $this->insertShare(IShare::TYPE_USERGROUP, IShare::STATUS_PENDING, 17);
		$idDeclined = $this->insertShare(IShare::TYPE_USERGROUP, IShare::STATUS_PENDING, 0);

		$this->commandTester->execute([]);

		$this->assertSame(IShare::STATUS_ACCEPTED, $this->getAccepted($id1));
		$this->assertSame(IShare::STATUS_ACCEPTED, $this->getAccepted($id2));
		$this->assertSame(IShare::STATUS_PENDING, $this->getAccepted($idDeclined));
		$this->assertStringContainsString('2', $this->commandTester->getDisplay());
	}
}
