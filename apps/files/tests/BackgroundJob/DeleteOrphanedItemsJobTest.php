<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\Tests\BackgroundJob;

use OCA\Files\BackgroundJob\DeleteOrphanedItems;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Class DeleteOrphanedItemsJobTest
 *
 * @group DB
 *
 * @package Test\BackgroundJob
 */
class DeleteOrphanedItemsJobTest extends \Test\TestCase {
	protected IDBConnection $connection;
	protected LoggerInterface $logger;
	protected ITimeFactory $timeFactory;

	protected function setUp(): void {
		parent::setUp();
		$this->connection = Server::get(IDBConnection::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->logger = Server::get(LoggerInterface::class);
	}

	protected function cleanMapping(string $table): void {
		$query = $this->connection->getQueryBuilder();
		$query->delete($table)->executeStatement();
	}

	protected function getMappings(string $table): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from($table);
		$result = $query->executeQuery();
		$mapping = $result->fetchAll();
		$result->closeCursor();

		return $mapping;
	}

	/**
	 * Test clearing orphaned system tag mappings
	 */
	public function testClearSystemTagMappings(): void {
		$this->cleanMapping('systemtag_object_mapping');

		$query = $this->connection->getQueryBuilder();
		$query->insert('filecache')
			->values([
				'storage' => $query->createNamedParameter(1337, IQueryBuilder::PARAM_INT),
				'path' => $query->createNamedParameter('apps/files/tests/deleteorphanedtagsjobtest.php'),
				'path_hash' => $query->createNamedParameter(md5('apps/files/tests/deleteorphanedtagsjobtest.php')),
			])->executeStatement();
		$fileId = $query->getLastInsertId();

		// Existing file
		$query = $this->connection->getQueryBuilder();
		$query->insert('systemtag_object_mapping')
			->values([
				'objectid' => $query->createNamedParameter($fileId, IQueryBuilder::PARAM_INT),
				'objecttype' => $query->createNamedParameter('files'),
				'systemtagid' => $query->createNamedParameter(1337, IQueryBuilder::PARAM_INT),
			])->executeStatement();

		// Non-existing file
		$query = $this->connection->getQueryBuilder();
		$query->insert('systemtag_object_mapping')
			->values([
				'objectid' => $query->createNamedParameter($fileId + 1, IQueryBuilder::PARAM_INT),
				'objecttype' => $query->createNamedParameter('files'),
				'systemtagid' => $query->createNamedParameter(1337, IQueryBuilder::PARAM_INT),
			])->executeStatement();

		$mapping = $this->getMappings('systemtag_object_mapping');
		$this->assertCount(2, $mapping);

		$job = new DeleteOrphanedItems($this->timeFactory, $this->connection, $this->logger);
		self::invokePrivate($job, 'cleanSystemTags');

		$mapping = $this->getMappings('systemtag_object_mapping');
		$this->assertCount(1, $mapping);

		$query = $this->connection->getQueryBuilder();
		$query->delete('filecache')
			->where($query->expr()->eq('fileid', $query->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)))
			->executeStatement();
		$this->cleanMapping('systemtag_object_mapping');
	}

	/**
	 * Test clearing orphaned system tag mappings
	 */
	public function testClearUserTagMappings(): void {
		$this->cleanMapping('vcategory_to_object');

		$query = $this->connection->getQueryBuilder();
		$query->insert('filecache')
			->values([
				'storage' => $query->createNamedParameter(1337, IQueryBuilder::PARAM_INT),
				'path' => $query->createNamedParameter('apps/files/tests/deleteorphanedtagsjobtest.php'),
				'path_hash' => $query->createNamedParameter(md5('apps/files/tests/deleteorphanedtagsjobtest.php')),
			])->executeStatement();
		$fileId = $query->getLastInsertId();

		// Existing file
		$query = $this->connection->getQueryBuilder();
		$query->insert('vcategory_to_object')
			->values([
				'objid' => $query->createNamedParameter($fileId, IQueryBuilder::PARAM_INT),
				'type' => $query->createNamedParameter('files'),
				'categoryid' => $query->createNamedParameter(1337, IQueryBuilder::PARAM_INT),
			])->executeStatement();

		// Non-existing file
		$query = $this->connection->getQueryBuilder();
		$query->insert('vcategory_to_object')
			->values([
				'objid' => $query->createNamedParameter($fileId + 1, IQueryBuilder::PARAM_INT),
				'type' => $query->createNamedParameter('files'),
				'categoryid' => $query->createNamedParameter(1337, IQueryBuilder::PARAM_INT),
			])->executeStatement();

		$mapping = $this->getMappings('vcategory_to_object');
		$this->assertCount(2, $mapping);

		$job = new DeleteOrphanedItems($this->timeFactory, $this->connection, $this->logger);
		self::invokePrivate($job, 'cleanUserTags');

		$mapping = $this->getMappings('vcategory_to_object');
		$this->assertCount(1, $mapping);

		$query = $this->connection->getQueryBuilder();
		$query->delete('filecache')
			->where($query->expr()->eq('fileid', $query->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)))
			->executeStatement();
		$this->cleanMapping('vcategory_to_object');
	}

	/**
	 * Test clearing orphaned system tag mappings
	 */
	public function testClearComments(): void {
		$this->cleanMapping('comments');

		$query = $this->connection->getQueryBuilder();
		$query->insert('filecache')
			->values([
				'storage' => $query->createNamedParameter(1337, IQueryBuilder::PARAM_INT),
				'path' => $query->createNamedParameter('apps/files/tests/deleteorphanedtagsjobtest.php'),
				'path_hash' => $query->createNamedParameter(md5('apps/files/tests/deleteorphanedtagsjobtest.php')),
			])->executeStatement();
		$fileId = $query->getLastInsertId();

		// Existing file
		$query = $this->connection->getQueryBuilder();
		$query->insert('comments')
			->values([
				'object_id' => $query->createNamedParameter($fileId, IQueryBuilder::PARAM_INT),
				'object_type' => $query->createNamedParameter('files'),
				'actor_id' => $query->createNamedParameter('Alice', IQueryBuilder::PARAM_INT),
				'actor_type' => $query->createNamedParameter('users'),
			])->executeStatement();

		// Non-existing file
		$query = $this->connection->getQueryBuilder();
		$query->insert('comments')
			->values([
				'object_id' => $query->createNamedParameter($fileId + 1, IQueryBuilder::PARAM_INT),
				'object_type' => $query->createNamedParameter('files'),
				'actor_id' => $query->createNamedParameter('Alice', IQueryBuilder::PARAM_INT),
				'actor_type' => $query->createNamedParameter('users'),
			])->executeStatement();

		$mapping = $this->getMappings('comments');
		$this->assertCount(2, $mapping);

		$job = new DeleteOrphanedItems($this->timeFactory, $this->connection, $this->logger);
		self::invokePrivate($job, 'cleanComments');

		$mapping = $this->getMappings('comments');
		$this->assertCount(1, $mapping);

		$query = $this->connection->getQueryBuilder();
		$query->delete('filecache')
			->where($query->expr()->eq('fileid', $query->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)))
			->executeStatement();
		$this->cleanMapping('comments');
	}

	/**
	 * Test clearing orphaned system tag mappings
	 */
	public function testClearCommentReadMarks(): void {
		$this->cleanMapping('comments_read_markers');

		$query = $this->connection->getQueryBuilder();
		$query->insert('filecache')
			->values([
				'storage' => $query->createNamedParameter(1337, IQueryBuilder::PARAM_INT),
				'path' => $query->createNamedParameter('apps/files/tests/deleteorphanedtagsjobtest.php'),
				'path_hash' => $query->createNamedParameter(md5('apps/files/tests/deleteorphanedtagsjobtest.php')),
			])->executeStatement();
		$fileId = $query->getLastInsertId();

		// Existing file
		$query = $this->connection->getQueryBuilder();
		$query->insert('comments_read_markers')
			->values([
				'object_id' => $query->createNamedParameter($fileId, IQueryBuilder::PARAM_INT),
				'object_type' => $query->createNamedParameter('files'),
				'user_id' => $query->createNamedParameter('Alice', IQueryBuilder::PARAM_INT),
			])->executeStatement();

		// Non-existing file
		$query = $this->connection->getQueryBuilder();
		$query->insert('comments_read_markers')
			->values([
				'object_id' => $query->createNamedParameter($fileId + 1, IQueryBuilder::PARAM_INT),
				'object_type' => $query->createNamedParameter('files'),
				'user_id' => $query->createNamedParameter('Alice', IQueryBuilder::PARAM_INT),
			])->executeStatement();

		$mapping = $this->getMappings('comments_read_markers');
		$this->assertCount(2, $mapping);

		$job = new DeleteOrphanedItems($this->timeFactory, $this->connection, $this->logger);
		self::invokePrivate($job, 'cleanCommentMarkers');

		$mapping = $this->getMappings('comments_read_markers');
		$this->assertCount(1, $mapping);

		$query = $this->connection->getQueryBuilder();
		$query->delete('filecache')
			->where($query->expr()->eq('fileid', $query->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)))
			->executeStatement();
		$this->cleanMapping('comments_read_markers');
	}
}
