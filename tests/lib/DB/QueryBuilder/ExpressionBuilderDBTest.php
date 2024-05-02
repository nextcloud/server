<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\DB\QueryBuilder;

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Types;
use OC\DB\QueryBuilder\Literal;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\Server;
use Test\TestCase;

/**
 * @group DB
 */
class ExpressionBuilderDBTest extends TestCase {
	/** @var \Doctrine\DBAL\Connection|\OCP\IDBConnection */
	protected $connection;
	protected $schemaSetup = false;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->prepareTestingTable();
	}

	public function likeProvider() {
		$connection = \OC::$server->getDatabaseConnection();

		return [
			['foo', 'bar', false],
			['foo', 'foo', true],
			['foo', 'f%', true],
			['foo', '%o', true],
			['foo', '%', true],
			['foo', 'fo_', true],
			['foo', 'foo_', false],
			['foo', $connection->escapeLikeParameter('fo_'), false],
			['foo', $connection->escapeLikeParameter('f%'), false],
		];
	}

	/**
	 * @dataProvider likeProvider
	 *
	 * @param string $param1
	 * @param string $param2
	 * @param boolean $match
	 */
	public function testLike($param1, $param2, $match) {
		$query = $this->connection->getQueryBuilder();

		$query->select(new Literal('1'))
			->from('users')
			->where($query->expr()->like($query->createNamedParameter($param1), $query->createNamedParameter($param2)));

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertEquals($match, $column);
	}

	public function ilikeProvider() {
		$connection = \OC::$server->getDatabaseConnection();

		return [
			['foo', 'bar', false],
			['foo', 'foo', true],
			['foo', 'Foo', true],
			['foo', 'f%', true],
			['foo', '%o', true],
			['foo', '%', true],
			['foo', 'fo_', true],
			['foo', 'foo_', false],
			['foo', $connection->escapeLikeParameter('fo_'), false],
			['foo', $connection->escapeLikeParameter('f%'), false],
		];
	}

	/**
	 * @dataProvider ilikeProvider
	 *
	 * @param string $param1
	 * @param string $param2
	 * @param boolean $match
	 */
	public function testILike($param1, $param2, $match) {
		$query = $this->connection->getQueryBuilder();

		$query->select(new Literal('1'))
			->from('users')
			->where($query->expr()->iLike($query->createNamedParameter($param1), $query->createNamedParameter($param2)));

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertEquals($match, $column);
	}

	public function testCastColumn(): void {
		$appId = $this->getUniqueID('testing');
		$this->createConfig($appId, '1', '4');

		$query = $this->connection->getQueryBuilder();
		$query->update('appconfig')
			->set('configvalue',
				$query->expr()->castColumn(
					$query->createFunction(
						'(' . $query->expr()->castColumn('configvalue', IQueryBuilder::PARAM_INT)
						. ' + 1)'
					), IQueryBuilder::PARAM_STR
				)
			)
			->where($query->expr()->eq('appid', $query->createNamedParameter($appId)))
			->andWhere($query->expr()->eq('configkey', $query->createNamedParameter('1')));

		$result = $query->executeStatement();
		$this->assertEquals(1, $result);
	}

	public function testLongText(): void {
		$appId = $this->getUniqueID('testing');
		$this->createConfig($appId, 'mykey', 'myvalue');

		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('appconfig')
			->where($query->expr()->eq('appid', $query->createNamedParameter($appId)))
			->andWhere($query->expr()->eq('configkey', $query->createNamedParameter('mykey')))
			->andWhere($query->expr()->eq('configvalue', $query->createNamedParameter('myvalue', IQueryBuilder::PARAM_STR), IQueryBuilder::PARAM_STR));

		$result = $query->executeQuery();
		$entries = $result->fetchAll();
		$result->closeCursor();
		self::assertCount(1, $entries);
		self::assertEquals('myvalue', $entries[0]['configvalue']);
	}

	public function testDateTimeEquals() {
		$dateTime = new \DateTime('2023-01-01');
		$insert = $this->connection->getQueryBuilder();
		$insert->insert('testing')
			->values(['datetime' => $insert->createNamedParameter($dateTime, IQueryBuilder::PARAM_DATE)])
			->executeStatement();

		$query = $this->connection->getQueryBuilder();
		$result = $query->select('*')
			->from('testing')
			->where($query->expr()->eq('datetime', $query->createNamedParameter($dateTime, IQueryBuilder::PARAM_DATE)))
			->executeQuery();
		$entries = $result->fetchAll();
		$result->closeCursor();
		self::assertCount(1, $entries);
	}

	public function testDateTimeLess() {
		$dateTime = new \DateTime('2022-01-01');
		$dateTimeCompare = new \DateTime('2022-01-02');
		$insert = $this->connection->getQueryBuilder();
		$insert->insert('testing')
			->values(['datetime' => $insert->createNamedParameter($dateTime, IQueryBuilder::PARAM_DATE)])
			->executeStatement();

		$query = $this->connection->getQueryBuilder();
		$result = $query->select('*')
			->from('testing')
			->where($query->expr()->lt('datetime', $query->createNamedParameter($dateTimeCompare, IQueryBuilder::PARAM_DATE)))
			->executeQuery();
		$entries = $result->fetchAll();
		$result->closeCursor();
		self::assertCount(1, $entries);
	}

	public function testDateTimeGreater() {
		$dateTime = new \DateTime('2023-01-02');
		$dateTimeCompare = new \DateTime('2023-01-01');
		$insert = $this->connection->getQueryBuilder();
		$insert->insert('testing')
			->values(['datetime' => $insert->createNamedParameter($dateTime, IQueryBuilder::PARAM_DATE)])
			->executeStatement();

		$query = $this->connection->getQueryBuilder();
		$result = $query->select('*')
			->from('testing')
			->where($query->expr()->gt('datetime', $query->createNamedParameter($dateTimeCompare, IQueryBuilder::PARAM_DATE)))
			->executeQuery();
		$entries = $result->fetchAll();
		$result->closeCursor();
		self::assertCount(1, $entries);
	}

	protected function createConfig($appId, $key, $value) {
		$query = $this->connection->getQueryBuilder();
		$query->insert('appconfig')
			->values([
				'appid' => $query->createNamedParameter($appId),
				'configkey' => $query->createNamedParameter((string) $key),
				'configvalue' => $query->createNamedParameter((string) $value),
			])
			->execute();
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

			$table->addColumn('datetime', Types::DATETIME_MUTABLE, [
				'notnull' => false,
			]);

			$table->setPrimaryKey(['id']);
			$this->connection->migrateToSchema($schema);
		}
	}
}
