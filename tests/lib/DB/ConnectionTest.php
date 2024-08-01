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
use Test\TestCase;

/**
 * @group DB
 */
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

}
