<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Settings\Tests;

use OC\L10N\L10N;
use OC\URLGenerator;
use OCA\Settings\SetupChecks\SqliteDatabase;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SqliteDatabaseTest extends TestCase {
	/** @var IConfig|MockObject */
	private $config;
	/** @var SqliteDatabase */
	private $check;

	public function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->check = new SqliteDatabase(
			$this->createMock(L10N::class),
			$this->config,
			$this->createMock(URLGenerator::class)
		);
	}

	public function testPass(): void {
		$this->config->method('getSystemValueString')
			->willReturn('mysql');
		$this->assertTrue($this->check->passes());
	}

	public function testFail(): void {
		$this->config->method('getSystemValueString')
			->willReturn('sqlite');
		$this->assertFalse($this->check->passes());
	}
}
