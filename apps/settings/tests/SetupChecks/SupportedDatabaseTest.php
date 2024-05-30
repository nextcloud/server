<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Morris Jobke <hey@morrisjobke.de>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
