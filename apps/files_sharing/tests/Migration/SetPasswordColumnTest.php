<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2017 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests\Migration;

use OCA\Files_Sharing\Migration\SetPasswordColumn;
use OCA\Files_Sharing\Tests\TestCase;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Server;
use OCP\Share\IShare;

/**
 * Class SetPasswordColumnTest
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class SetPasswordColumnTest extends TestCase {

	/** @var IDBConnection */
	private $connection;

	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;

	/** @var SetPasswordColumn */
	private $migration;

	private $table = 'share';

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->config = $this->createMock(IConfig::class);
		$this->migration = new SetPasswordColumn($this->connection, $this->config);

		$this->cleanDB();
	}

	protected function tearDown(): void {
		parent::tearDown();
		$this->cleanDB();
	}

	private function cleanDB() {
		$query = $this->connection->getQueryBuilder();
		$query->delete($this->table)->executeStatement();
	}

	public function testAddPasswordColumn(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('files_sharing', 'installed_version', '0.0.0')
			->willReturn('1.3.0');

		$shareTypes = [IShare::TYPE_USER, IShare::TYPE_GROUP, IShare::TYPE_REMOTE, IShare::TYPE_EMAIL, IShare::TYPE_LINK];

		foreach ($shareTypes as $shareType) {
			for ($i = 0; $i < 5; $i++) {
				$query = $this->connection->getQueryBuilder();
				$query->insert($this->table)
					->values([
						'share_type' => $query->createNamedParameter($shareType),
						'share_with' => $query->createNamedParameter('shareWith'),
						'uid_owner' => $query->createNamedParameter('user' . $i),
						'uid_initiator' => $query->createNamedParameter(null),
						'parent' => $query->createNamedParameter(0),
						'item_type' => $query->createNamedParameter('file'),
						'item_source' => $query->createNamedParameter('2'),
						'item_target' => $query->createNamedParameter('/2'),
						'file_source' => $query->createNamedParameter(2),
						'file_target' => $query->createNamedParameter('/foobar'),
						'permissions' => $query->createNamedParameter(31),
						'stime' => $query->createNamedParameter(time()),
					]);

				$this->assertSame(1, $query->executeStatement());
			}
		}

		/** @var IOutput $output */
		$output = $this->createMock(IOutput::class);
		$this->migration->run($output);

		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('share');
		$result = $query->executeQuery();
		$allShares = $result->fetchAllAssociative();
		$result->closeCursor();

		foreach ($allShares as $share) {
			if ((int)$share['share_type'] === IShare::TYPE_LINK) {
				$this->assertNull($share['share_with']);
				$this->assertSame('shareWith', $share['password']);
			} else {
				$this->assertSame('shareWith', $share['share_with']);
				$this->assertNull($share['password']);
			}
		}
	}
}
