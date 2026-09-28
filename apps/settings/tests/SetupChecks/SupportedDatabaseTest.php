<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\SetupChecks;

use OCA\Settings\SetupChecks\SupportedDatabase;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Server;
use OCP\SetupCheck\SetupResult;
use Test\TestCase;

/**
 * @group DB
 */
class SupportedDatabaseTest extends TestCase {
	private IL10N $l10n;
	private IUrlGenerator $urlGenerator;
	private IDBConnection $connection;

	private SupportedDatabase $check;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->connection = Server::get(IDBConnection::class);

		$this->check = new SupportedDatabase(
			$this->l10n,
			$this->urlGenerator,
			Server::get(IDBConnection::class)
		);
	}

	public function testPass(): void {
		$severities = [SetupResult::SUCCESS, SetupResult::INFO];
		if ($this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_SQLITE) {
			$severities = [SetupResult::WARNING];
		} elseif ($this->connection->getDatabaseProvider(true) === IDBConnection::PLATFORM_ORACLE) {
			$result = $this->connection->executeQuery('SELECT VERSION FROM PRODUCT_COMPONENT_VERSION');
			$version = $result->fetchOne();
			$result->closeCursor();
			if (str_starts_with($version, '11.')) {
				$severities = [SetupResult::WARNING];
			}
		}

		$this->assertContains($this->check->run()->getSeverity(), $severities, 'Oracle 11 and SQLite expect a warning, other databases should be success or info only');
	}
}
