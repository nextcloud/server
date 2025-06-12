<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Db;

use Doctrine\DBAL\Schema\SchemaException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Server;
use Test\TestCase;

/**
 * @method void setTime(?\DateTime $time)
 * @method ?\DateTime getTime()
 * @method void setDatetime(?\DateTimeImmutable $datetime)
 * @method ?\DateTimeImmutable getDatetime()
 */
class QBDBTestEntity extends Entity {
	protected ?\DateTime $time = null;
	protected ?\DateTimeImmutable $datetime = null;

	public function __construct() {
		$this->addType('time', Types::TIME);
		$this->addType('datetime', Types::DATETIME_IMMUTABLE);
	}
}

/**
 * Class QBDBTestMapper
 *
 * @package Test\AppFramework\Db
 */
class QBDBTestMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'testing', QBDBTestEntity::class);
	}

	public function getParameterTypeForPropertyForTest(Entity $entity, string $property) {
		return parent::getParameterTypeForProperty($entity, $property);
	}

	public function getById(int $id): QBDBTestEntity {
		$qb = $this->db->getQueryBuilder();
		$query = $qb
			->select('*')
			->from($this->tableName)
			->where(
				$qb->expr()->eq('id', $qb->createPositionalParameter($id, IQueryBuilder::PARAM_INT)),
			);
		return $this->findEntity($query);
	}
}

/**
 * Test real database handling (serialization)
 * @group DB
 */
class QBMapperDBTest extends TestCase {
	/** @var \Doctrine\DBAL\Connection|\OCP\IDBConnection */
	protected $connection;
	protected $schemaSetup = false;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->prepareTestingTable();
	}

	public function testInsertDateTime(): void {
		$mapper = new QBDBTestMapper($this->connection);
		$entity = new QBDBTestEntity();
		$entity->setTime(new \DateTime('2003-01-01 12:34:00'));
		$entity->setDatetime(new \DateTimeImmutable('2000-01-01 23:45:00'));

		$result = $mapper->insert($entity);
		$this->assertNotNull($result->getId());
	}

	public function testRetrieveDateTime(): void {
		$time = new \DateTime('2000-01-01 01:01:00');
		$datetime = new \DateTimeImmutable('2000-01-01 02:02:00');

		$mapper = new QBDBTestMapper($this->connection);
		$entity = new QBDBTestEntity();
		$entity->setTime($time);
		$entity->setDatetime($datetime);

		$result = $mapper->insert($entity);
		$this->assertNotNull($result->getId());

		$dbEntity = $mapper->getById($result->getId());
		$this->assertEquals($time->format('H:i:s'), $dbEntity->getTime()->format('H:i:s'));
		$this->assertEquals($datetime->format('Y-m-d H:i:s'), $dbEntity->getDatetime()->format('Y-m-d H:i:s'));
		// The date is not saved for "time"
		$this->assertNotEquals($time->format('Y'), $dbEntity->getTime()->format('Y'));
	}

	public function testUpdateDateTime(): void {
		$time = new \DateTime('2000-01-01 01:01:00');
		$datetime = new \DateTimeImmutable('2000-01-01 02:02:00');

		$mapper = new QBDBTestMapper($this->connection);
		$entity = new QBDBTestEntity();
		$entity->setTime('now');
		$entity->setDatetime('now');

		/** @var QBDBTestEntity */
		$entity = $mapper->insert($entity);
		$this->assertNotNull($entity->getId());

		// Update the values
		$entity->setTime($time);
		$entity->setDatetime($datetime);
		$mapper->update($entity);

		$dbEntity = $mapper->getById($entity->getId());
		$this->assertEquals($time->format('H:i:s'), $dbEntity->getTime()->format('H:i:s'));
		$this->assertEquals($datetime->format('Y-m-d H:i:s'), $dbEntity->getDatetime()->format('Y-m-d H:i:s'));
	}

	protected function prepareTestingTable(): void {
		if ($this->schemaSetup) {
			$this->connection->getQueryBuilder()->delete('testing')->executeStatement();
		}

		$prefix = Server::get(IConfig::class)->getSystemValueString('dbtableprefix', 'oc_');
		$schema = $this->connection->createSchema();
		try {
			$schema->getTable($prefix . 'testing');
			$this->connection->getQueryBuilder()->delete('testing')->executeStatement();
		} catch (SchemaException $e) {
			$this->schemaSetup = true;
			$table = $schema->createTable($prefix . 'testing');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
			]);

			$table->addColumn('time', Types::TIME, [
				'notnull' => false,
			]);

			$table->addColumn('datetime', Types::DATETIME_IMMUTABLE, [
				'notnull' => false,
			]);

			$table->setPrimaryKey(['id']);
			$this->connection->migrateToSchema($schema);
		}
	}
}
