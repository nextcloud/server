<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\DB\Middleware;

use OC\DB\Connection;
use OC\DB\ConnectionFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

/**
 * We cannot test the actual driver here,
 * but we can at least test that it does what we want.
 */
#[Group('DB')]
final class UtcTimezoneMiddlewareDriverTest extends TestCase {
	private ?Connection $connection = null;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->connection = $this->getRootDbConnection();
		if ($this->connection === null) {
			$this->markTestSkipped('No root database credentials provided (DB_ROOT_USER, DB_ROOT_PASSWORD), cannot run test');
			return;
		}

		$provider = $this->connection->getDatabaseProvider();
		if ($provider === IDBConnection::PLATFORM_MARIADB || $provider === IDBConnection::PLATFORM_MYSQL) {
			$this->connection->executeStatement("SET GLOBAL time_zone = 'America/New_York'");
		} else {
			$this->markTestSkipped('This test only works with MySQL/MariaDB');
		}
	}

	#[\Override]
	protected function tearDown(): void {
		if ($this->connection !== null) {
			$provider = $this->connection->getDatabaseProvider();
			if ($provider === IDBConnection::PLATFORM_MARIADB || $provider === IDBConnection::PLATFORM_MYSQL) {
				$this->connection->executeStatement("SET GLOBAL time_zone = 'SYSTEM'");
			}
			$this->connection->close();
		}

		parent::tearDown();
	}

	public function testSqlNowIsInUtc() {
		$connection = $this->getDbConnection();
		$result = $connection->executeQuery('SELECT NOW()');
		$data = $result->fetchOne();
		$connection->close();

		self::assertIsString($data, 'Expected a string from the database');

		$expected = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
		$received = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data, new \DateTimeZone('UTC'));

		$diff = abs($received->getTimestamp() - $expected->getTimestamp());
		self::assertLessThan(15 * 60, $diff); // allow up to 15 minutes of difference, to account for slow test environments and time sync issues.
	}

	/**
	 * Get a new database connection.
	 * This is needed because the setup is changing the global timezone setting,
	 * but its only applied for new connections.
	 */
	private function getDbConnection(array $overrides = []): Connection {
		$config = Server::get(IConfig::class);
		$cf = Server::get(ConnectionFactory::class);
		return $cf->getConnection(
			$config->getSystemValue('dbtype'),
			[
				'host' => $config->getSystemValue('dbhost'),
				'user' => $config->getSystemValue('dbuser'),
				'password' => $config->getSystemValue('dbpassword'),
				'tablePrefix' => $config->getSystemValue('dbtableprefix'),
				'dbname' => $config->getSystemValue('dbname'),
				...$overrides,
			],
		);
	}
	/**
	 * Get the database connection as root user,
	 * so that we can change the global timezone setting.
	 */
	private function getRootDbConnection(): ?Connection {
		$rootUser = getenv('DB_ROOT_USER') ?: '';
		$rootPassword = getenv('DB_ROOT_PASSWORD') ?: '';
		if ($rootPassword === '' || $rootUser === '') {
			return null;
		}

		return $this->getDbConnection([
			'user' => $rootUser,
			'password' => $rootPassword,
		]);
	}
}
