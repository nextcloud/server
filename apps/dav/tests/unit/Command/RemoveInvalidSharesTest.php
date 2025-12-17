<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2018 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Command;

use OCA\DAV\Command\RemoveInvalidShares;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\RemoteUserPrincipalBackend;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

/**
 * Class RemoveInvalidSharesTest
 *
 * @package OCA\DAV\Tests\Unit\Repair
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class RemoveInvalidSharesTest extends TestCase {
	private RemoveInvalidShares $command;

	private IDBConnection $db;
	private Principal&MockObject $principalBackend;
	private RemoteUserPrincipalBackend&MockObject $remoteUserPrincipalBackend;

	protected function setUp(): void {
		parent::setUp();

		$this->db = Server::get(IDBConnection::class);
		$this->principalBackend = $this->createMock(Principal::class);
		$this->remoteUserPrincipalBackend = $this->createMock(RemoteUserPrincipalBackend::class);

		$qb = $this->db->getQueryBuilder();
		$qb->insert('dav_shares');

		foreach (['principal:unknown', 'principals/remote-users/foobar'] as $principaluri) {
			$qb->values([
				'principaluri' => $qb->createNamedParameter($principaluri),
				'type' => $qb->createNamedParameter('calendar'),
				'access' => $qb->createNamedParameter(2, IQueryBuilder::PARAM_INT),
				'resourceid' => $qb->createNamedParameter(666, IQueryBuilder::PARAM_INT),
			]);

			try {
				$qb->executeStatement();
			} catch (Exception $e) {
				if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					throw $e;
				}
			}
		}

		$this->command = new RemoveInvalidShares(
			$this->db,
			$this->principalBackend,
			$this->remoteUserPrincipalBackend,
		);
	}

	private function selectShares(): array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('dav_shares')
			->where($query->expr()->in(
				'principaluri',
				$query->createNamedParameter(
					['principal:unknown', 'principals/remote-users/foobar'],
					IQueryBuilder::PARAM_STR_ARRAY,
				),
			));
		$result = $query->executeQuery();
		$data = $result->fetchAllAssociative();
		$result->closeCursor();

		return $data;
	}

	public function testWithoutPrincipals(): void {
		$this->principalBackend->method('getPrincipalByPath')
			->willReturnMap([
				['principal:unknown', null],
				['principals/remote-users/foobar', null],
			]);
		$this->remoteUserPrincipalBackend->method('getPrincipalByPath')
			->willReturnMap([
				['principal:unknown', null],
				['principals/remote-users/foobar', null],
			]);

		$this->command->run(
			$this->createMock(InputInterface::class),
			$this->createMock(OutputInterface::class),
		);

		$data = $this->selectShares();
		$this->assertCount(0, $data);
	}

	public function testWithLocalPrincipal(): void {
		$this->principalBackend->method('getPrincipalByPath')
			->willReturnMap([
				['principal:unknown', ['uri' => 'principal:unknown']],
				['principals/remote-users/foobar', null],
			]);
		$this->remoteUserPrincipalBackend->method('getPrincipalByPath')
			->willReturnMap([
				['principals/remote-users/foobar', null],
			]);

		$this->command->run(
			$this->createMock(InputInterface::class),
			$this->createMock(OutputInterface::class),
		);

		$data = $this->selectShares();
		$this->assertCount(1, $data);
		$this->assertEquals('principal:unknown', $data[0]['principaluri']);
	}

	public function testWithRemotePrincipal() {
		$this->principalBackend->method('getPrincipalByPath')
			->willReturnMap([
				['principal:unknown', null],
				['principals/remote-users/foobar', null],
			]);
		$this->remoteUserPrincipalBackend->method('getPrincipalByPath')
			->willReturnMap([
				['principal:unknown', null],
				['principals/remote-users/foobar', ['uri' => 'principals/remote-users/foobar']],
			]);

		$this->command->run(
			$this->createMock(InputInterface::class),
			$this->createMock(OutputInterface::class),
		);

		$data = $this->selectShares();
		$this->assertCount(1, $data);
		$this->assertEquals('principals/remote-users/foobar', $data[0]['principaluri']);
	}
}
