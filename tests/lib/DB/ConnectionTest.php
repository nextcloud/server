<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\DB;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use OC\DB\Adapter;
use OC\DB\Connection;
use OC\DB\ConnectionAdapter;
use OCP\IDBConnection;
use OCP\Server;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class ConnectionTest extends TestCase {

	public function testSingleNodeConnectsToPrimaryOnly(): void {
		$connectionParams = [
			'user' => 'test',
			'password' => 'topsecret',
			'host' => 'test',
		];
		$adapter = $this->createMock(Adapter::class);
		$driver = $this->createMock(Driver::class);
		$configuration = $this->createMock(Configuration::class);
		$connection = $this->getMockBuilder(Connection::class)
			->onlyMethods(['connectTo'])
			->setConstructorArgs([
				[
					'adapter' => $adapter,
					'platform' => new MySQLPlatform(),
					'tablePrefix' => 'nctest',
					'primary' => $connectionParams,
					'replica' => [
						$connectionParams,
					],
				],
				$driver,
				$configuration,
			])
			->getMock();
		$driverConnection = $this->createMock(DriverConnection::class);
		$connection->expects(self::once())
			->method('connectTo')
			->with('primary')
			->willReturn($driverConnection);

		$connection->ensureConnectedToReplica();
		$connection->ensureConnectedToPrimary();
		$connection->ensureConnectedToReplica();
	}

	public function testClusterConnectsToPrimaryAndReplica(): void {
		$connectionParamsPrimary = [
			'user' => 'test',
			'password' => 'topsecret',
			'host' => 'testprimary',
		];
		$connectionParamsReplica = [
			'user' => 'test',
			'password' => 'topsecret',
			'host' => 'testreplica',
		];
		$adapter = $this->createMock(Adapter::class);
		$driver = $this->createMock(Driver::class);
		$configuration = $this->createMock(Configuration::class);
		$connection = $this->getMockBuilder(Connection::class)
			->onlyMethods(['connectTo'])
			->setConstructorArgs([
				[
					'adapter' => $adapter,
					'platform' => new MySQLPlatform(),
					'tablePrefix' => 'nctest',
					'primary' => $connectionParamsPrimary,
					'replica' => [
						$connectionParamsReplica,
					],
				],
				$driver,
				$configuration,
			])
			->getMock();
		$driverConnection = $this->createMock(DriverConnection::class);
		$connection->expects(self::exactly(2))
			->method('connectTo')
			->willReturn($driverConnection);

		$connection->ensureConnectedToReplica();
		$connection->ensureConnectedToPrimary();
		$connection->ensureConnectedToReplica();
	}

	public function testSuccessfulQueryResetsConnectivityCheckTimer(): void {
		$inner = $this->getInnerConnection();

		// Ensure the connection is established before touching the timer
		$qb = $inner->getQueryBuilder();
		$qb->select('configvalue')->from('appconfig')->setMaxResults(1);
		$qb->executeQuery()->closeCursor();

		$property = $this->backdateLastConnectionCheck($inner);
		$before = time();

		$qb->executeQuery()->closeCursor();

		// A connectivity probe firing between adjacent operations would reset
		// the driver level last insert id on MySQL
		self::assertGreaterThanOrEqual($before, max($property->getValue($inner)));
	}

	public function testPreparedStatementExecutionResetsConnectivityCheckTimer(): void {
		$inner = $this->getInnerConnection();

		$statement = $inner->prepare('SELECT `configvalue` FROM `*PREFIX*appconfig`', 1);

		$property = $this->backdateLastConnectionCheck($inner);
		$before = time();

		$statement->executeQuery()->free();

		self::assertGreaterThanOrEqual($before, max($property->getValue($inner)));
	}

	private function getInnerConnection(): Connection {
		$connection = Server::get(IDBConnection::class);
		if (!$connection instanceof ConnectionAdapter) {
			self::markTestSkipped('Test requires the real database connection');
		}

		return $connection->getInner();
	}

	/**
	 * Make the connectivity check timer stale, but by less than the check
	 * interval: the probe must not fire, so only actual query activity can
	 * refresh the timer.
	 */
	private function backdateLastConnectionCheck(Connection $connection): \ReflectionProperty {
		$property = new \ReflectionProperty(Connection::class, 'lastConnectionCheck');
		$property->setValue($connection, ['primary' => time() - 20, 'replica' => time() - 20]);

		return $property;
	}

}
