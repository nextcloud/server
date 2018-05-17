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

	protected function setUp() {
		parent::setUp();

		$this->connection = \OC::$server->getDatabaseConnection();
	}

	public function testConcat() {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->concat($query->createNamedParameter('foo'), new Literal("'bar'")));
		$query->from('appconfig')
			->setMaxResults(1);

		$this->assertEquals('foobar', $query->execute()->fetchColumn());
	}

	public function testMd5() {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->md5($query->createNamedParameter('foobar')));
		$query->from('appconfig')
			->setMaxResults(1);

		$this->assertEquals(md5('foobar'), $query->execute()->fetchColumn());
	}

	public function testSubstring() {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->substring($query->createNamedParameter('foobar'), new Literal(2), $query->createNamedParameter(2)));
		$query->from('appconfig')
			->setMaxResults(1);

		$this->assertEquals('oo', $query->execute()->fetchColumn());
	}

	public function testSubstringNoLength() {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->substring($query->createNamedParameter('foobar'), new Literal(2)));
		$query->from('appconfig')
			->setMaxResults(1);

		$this->assertEquals('oobar', $query->execute()->fetchColumn());
	}

	public function testLower() {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->lower($query->createNamedParameter('FooBar')));
		$query->from('appconfig')
			->setMaxResults(1);

		$this->assertEquals('foobar', $query->execute()->fetchColumn());
	}

	public function testAdd() {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->add($query->createNamedParameter(2, IQueryBuilder::PARAM_INT), new Literal(1)));
		$query->from('appconfig')
			->setMaxResults(1);

		$this->assertEquals(3, $query->execute()->fetchColumn());
	}

	public function testSubtract() {
		$query = $this->connection->getQueryBuilder();

		$query->select($query->func()->subtract($query->createNamedParameter(2, IQueryBuilder::PARAM_INT), new Literal(1)));
		$query->from('appconfig')
			->setMaxResults(1);

		$this->assertEquals(1, $query->execute()->fetchColumn());
	}
}
