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

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
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
		if ($this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_SQLITE) {
			/** SQlite always gets a warning */
			$this->assertEquals(SetupResult::WARNING, $this->check->run()->getSeverity());
		} else {
			$this->assertContains($this->check->run()->getSeverity(), [SetupResult::SUCCESS, SetupResult::INFO]);
		}
	}
}
