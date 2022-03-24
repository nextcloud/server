<?php
/**
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test\DB\QueryBuilder;

use OC\DB\QueryBuilder\Literal;
use OCP\DB\QueryBuilder\IQueryBuilder;
use Test\TestCase;

/**
 * Class FunctionBuilderTest
 *
 * @group DB
 *
 * @package Test\DB\QueryBuilder
 */
class FunctionBuilderTest extends TestCase {
	/** @var \Doctrine\DBAL\Connection|\OCP\IDBConnection */
	protected $connection;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = \OC::$server->getDatabaseConnection();
	}

	/**
	 * @dataProvider providerTestConcatString
	 */
	public function testConcatString($closure) {
		$query = $this->connection->getQueryBuilder();
		[$real, $arguments, $return] = $closure($query);
		if ($real) {
			$this->addDummyData();
			$query->where($query->expr()->eq('appid', $query->createNamedParameter('group_concat')));
		}

		$query->select($query->func()->concat(...$arguments));
		$query->from('appconfig')
			->setMaxResults(1);

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertEquals($return, $column);
	}

	public function providerTestConcatString(): array {
		return [
			'1 column: string param unicode' =>
				[function ($q) {
					return [false, [$q->createNamedParameter('👍')], '👍'];
				}],
			'2 columns: string param and string param' =>
				[function ($q) {
					return [false, [$q->createNamedParameter('foo'), $q->createNamedParameter('bar')], 'foobar'];
				}],
			'2 columns: string param and int literal' =>
				[function ($q) {
					return [false, [$q->createNamedParameter('foo'), $q->expr()->literal(1)], 'foo1'];
				}],
			'2 columns: string param and string literal' =>
				[function ($q) {
					return [false, [$q->createNamedParameter('foo'), $q->expr()->literal('bar')], 'foobar'];
				}],
			'2 columns: string real and int literal' =>
				[function ($q) {
					return [true, ['configkey', $q->expr()->literal(2)], '12'];
				}],
			'4 columns: string literal' =>
				[function ($q) {
					return [false, [$q->expr()->literal('foo'), $q->expr()->literal('bar'), $q->expr()->literal('foo'), $q->expr()->literal('bar')], 'foobarfoobar'];
				}],
			'4 columns: int literal' =>
				[function ($q) {
					return [false, [$q->expr()->literal(1), $q->expr()->literal(2), $q->expr()->literal(3), $q->expr()->literal(4)], '1234'];
				}],
			'5 columns: string param with special chars used in the function' =>
				[function ($q) {
					return [false, [$q->createNamedParameter("b"), $q->createNamedParameter("'"), $q->createNamedParameter('||'), $q->createNamedParameter(','), $q->createNamedParameter('a')], "b'||,a"];
				}],
		];
	}

	protected function clearDummyData(): void {
		$delete = $this->connection->getQueryBuilder();

		$delete->delete('appconfig')
			->where($delete->expr()->eq('appid', $delete->createNamedParameter('group_concat')));
		$delete->executeStatement();
	}

	protected function addDummyData(): void {
		$this->clearDummyData();
		$insert = $this->connection->getQueryBuilder();

		$insert->insert('appconfig')
			->setValue('appid', $insert->createNamedParameter('group_concat'))
			->setValue('configvalue', $insert->createNamedParameter('unittest'))
			->setValue('configkey', $insert->createParameter('value'));

		$insert->setParameter('value', '1');
		$insert->executeStatement();
		$insert->setParameter('value', '3');
		$insert->executeStatement();
		$insert->setParameter('value', '2');
		$insert->executeStatement();
	}

	public function testGroupConcatWithoutSeparator(): void {
		$this->addDummyData();
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->groupConcat('configkey'))
			->from('appconfig')
			->where($query->expr()->eq('appid', $query->createNamedParameter('group_concat')));

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertStringContainsString(',', $column);
		$actual = explode(',', $column);
		$this->assertEqualsCanonicalizing([1,2,3], $actual);
	}

	public function testGroupConcatWithSeparator(): void {
		$this->addDummyData();
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->groupConcat('configkey', '#'))
			->from('appconfig')
			->where($query->expr()->eq('appid', $query->createNamedParameter('group_concat')));

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertStringContainsString('#', $column);
		$actual = explode('#', $column);
		$this->assertEqualsCanonicalizing([1,2,3], $actual);
	}

	public function testGroupConcatWithSingleQuoteSeparator(): void {
		$this->addDummyData();
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->groupConcat('configkey', '\''))
			->from('appconfig')
			->where($query->expr()->eq('appid', $query->createNamedParameter('group_concat')));

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertStringContainsString("'", $column);
		$actual = explode("'", $column);
		$this->assertEqualsCanonicalizing([1,2,3], $actual);
	}

	public function testGroupConcatWithDoubleQuoteSeparator(): void {
		$this->addDummyData();
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->groupConcat('configkey', '"'))
			->from('appconfig')
			->where($query->expr()->eq('appid', $query->createNamedParameter('group_concat')));

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertStringContainsString('"', $column);
		$actual = explode('"', $column);
		$this->assertEqualsCanonicalizing([1,2,3], $actual);
	}

	protected function clearIntDummyData(): void {
		$delete = $this->connection->getQueryBuilder();

		$delete->delete('systemtag')
			->where($delete->expr()->eq('name', $delete->createNamedParameter('group_concat')));
		$delete->executeStatement();
	}

	protected function addIntDummyData(): void {
		$this->clearIntDummyData();
		$insert = $this->connection->getQueryBuilder();

		$insert->insert('systemtag')
			->setValue('name', $insert->createNamedParameter('group_concat'))
			->setValue('visibility', $insert->createNamedParameter(1))
			->setValue('editable', $insert->createParameter('value'));

		$insert->setParameter('value', 1);
		$insert->executeStatement();
		$insert->setParameter('value', 2);
		$insert->executeStatement();
		$insert->setParameter('value', 3);
		$insert->executeStatement();
	}

	public function testIntGroupConcatWithoutSeparator(): void {
		$this->addIntDummyData();
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->groupConcat('editable'))
			->from('systemtag')
			->where($query->expr()->eq('name', $query->createNamedParameter('group_concat')));

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertStringContainsString(',', $column);
		$actual = explode(',', $column);
		$this->assertEqualsCanonicalizing([1,2,3], $actual);
	}

	public function testIntGroupConcatWithSeparator(): void {
		$this->addIntDummyData();
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->groupConcat('editable', '#'))
			->from('systemtag')
			->where($query->expr()->eq('name', $query->createNamedParameter('group_concat')));

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertStringContainsString('#', $column);
		$actual = explode('#', $column);
		$this->assertEqualsCanonicalizing([1,2,3], $actual);
	}

	public function testMd5() {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->md5($query->createNamedParameter('foobar')));
		$query->from('appconfig')
			->setMaxResults(1);

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertEquals(md5('foobar'), $column);
	}

	public function testSubstring() {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->substring($query->createNamedParameter('foobar'), new Literal(2), $query->createNamedParameter(2)));
		$query->from('appconfig')
			->setMaxResults(1);

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertEquals('oo', $column);
	}

	public function testSubstringNoLength() {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->substring($query->createNamedParameter('foobar'), new Literal(2)));
		$query->from('appconfig')
			->setMaxResults(1);

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertEquals('oobar', $column);
	}

	public function testLower() {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->lower($query->createNamedParameter('FooBar')));
		$query->from('appconfig')
			->setMaxResults(1);

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertEquals('foobar', $column);
	}

	public function testAdd() {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->add($query->createNamedParameter(2, IQueryBuilder::PARAM_INT), new Literal(1)));
		$query->from('appconfig')
			->setMaxResults(1);

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertEquals(3, $column);
	}

	public function testSubtract() {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->subtract($query->createNamedParameter(2, IQueryBuilder::PARAM_INT), new Literal(1)));
		$query->from('appconfig')
			->setMaxResults(1);

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertEquals(1, $column);
	}

	public function testCount() {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->count('appid'));
		$query->from('appconfig')
			->setMaxResults(1);

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertGreaterThan(1, $column);
	}

	public function octetLengthProvider() {
		return [
			['', 0],
			['foobar', 6],
			['fé', 3],
			['šđčćž', 10],
		];
	}

	/**
	 * @dataProvider octetLengthProvider
	 */
	public function testOctetLength(string $str, int $bytes) {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->octetLength($query->createNamedParameter($str, IQueryBuilder::PARAM_STR)));

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertEquals($bytes, $column);
	}

	public function charLengthProvider() {
		return [
			['', 0],
			['foobar', 6],
			['fé', 2],
			['šđčćž', 5],
		];
	}

	/**
	 * @dataProvider charLengthProvider
	 */
	public function testCharLength(string $str, int $bytes) {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->charLength($query->createNamedParameter($str, IQueryBuilder::PARAM_STR)));

		$result = $query->execute();
		$column = $result->fetchOne();
		$result->closeCursor();
		$this->assertEquals($bytes, $column);
	}

	private function setUpMinMax($value) {
		$query = $this->connection->getQueryBuilder();

		$query->insert('appconfig')
			->values([
				'appid' => $query->createNamedParameter('minmax'),
				'configkey' => $query->createNamedParameter(uniqid()),
				'configvalue' => $query->createNamedParameter((string)$value),
			]);
		$query->execute();
	}

	private function clearMinMax() {
		$query = $this->connection->getQueryBuilder();

		$query->delete('appconfig')
			->where($query->expr()->eq('appid', $query->createNamedParameter('minmax')));
		$query->execute();
	}

	public function testMaxEmpty() {
		$this->clearMinMax();

		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->max($query->expr()->castColumn('configvalue', IQueryBuilder::PARAM_INT)));
		$query->from('appconfig')
			->where($query->expr()->eq('appid', $query->createNamedParameter('minmax')))
			->setMaxResults(1);

		$result = $query->execute();
		$row = $result->fetchOne();
		$result->closeCursor();
		$this->assertEquals(null, $row);
	}

	public function testMinEmpty() {
		$this->clearMinMax();

		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->min($query->expr()->castColumn('configvalue', IQueryBuilder::PARAM_INT)));
		$query->from('appconfig')
			->where($query->expr()->eq('appid', $query->createNamedParameter('minmax')))
			->setMaxResults(1);

		$result = $query->execute();
		$row = $result->fetchOne();
		$result->closeCursor();
		$this->assertEquals(null, $row);
	}

	public function testMax() {
		$this->clearMinMax();
		$this->setUpMinMax(10);
		$this->setUpMinMax(11);
		$this->setUpMinMax(20);

		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->max($query->expr()->castColumn('configvalue', IQueryBuilder::PARAM_INT)));
		$query->from('appconfig')
			->where($query->expr()->eq('appid', $query->createNamedParameter('minmax')))
			->setMaxResults(1);

		$result = $query->execute();
		$row = $result->fetchOne();
		$result->closeCursor();
		$this->assertEquals(20, $row);
	}

	public function testMin() {
		$this->clearMinMax();
		$this->setUpMinMax(10);
		$this->setUpMinMax(11);
		$this->setUpMinMax(20);

		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->min($query->expr()->castColumn('configvalue', IQueryBuilder::PARAM_INT)));
		$query->from('appconfig')
			->where($query->expr()->eq('appid', $query->createNamedParameter('minmax')))
			->setMaxResults(1);

		$result = $query->execute();
		$row = $result->fetchOne();
		$result->closeCursor();
		$this->assertEquals(10, $row);
	}

	public function testGreatest() {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->greatest($query->createNamedParameter(2, IQueryBuilder::PARAM_INT), new Literal(1)));
		$query->from('appconfig')
			->setMaxResults(1);

		$result = $query->execute();
		$row = $result->fetchOne();
		$result->closeCursor();
		$this->assertEquals(2, $row);
	}

	public function testLeast() {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->least($query->createNamedParameter(2, IQueryBuilder::PARAM_INT), new Literal(1)));
		$query->from('appconfig')
			->setMaxResults(1);

		$result = $query->execute();
		$row = $result->fetchOne();
		$result->closeCursor();
		$this->assertEquals(1, $row);
	}
}
