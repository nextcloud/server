<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use OCA\Settings\SetupChecks\SupportedDatabase;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUrlGenerator;
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

		$this->l10n = $this->getMockBuilder(IL10N::class)->getMock();
		$this->urlGenerator = $this->getMockBuilder(IUrlGenerator::class)->getMock();
		$this->connection = \OCP\Server::get(IDBConnection::class);

		$this->check = new SupportedDatabase(
			$this->l10n,
			$this->urlGenerator,
			\OCP\Server::get(IDBConnection::class)
		);
	}

	public function testPass(): void {
		$platform = $this->connection->getDatabasePlatform();
		if ($platform instanceof SqlitePlatform) {
			/** SQlite always gets a warning */
			$this->assertEquals(SetupResult::WARNING, $this->check->run()->getSeverity());
		} else {
			$this->assertEquals(SetupResult::SUCCESS, $this->check->run()->getSeverity());
		}
	}
}
