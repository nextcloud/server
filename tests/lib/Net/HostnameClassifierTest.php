<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace lib\Net;

use OC\Net\HostnameClassifier;
use Test\TestCase;

class HostnameClassifierTest extends TestCase {
	private HostnameClassifier $classifier;

	protected function setUp(): void {
		parent::setUp();

		$this->classifier = new HostnameClassifier();
	}

	public function localHostnamesData():array {
		return [
			['localhost'],
			['localHost'],
			['random-host'],
			['another-host.local'],
			['service.localhost'],
			['randomdomain.internal'],
		];
	}

	/**
	 * @dataProvider localHostnamesData
	 */
	public function testLocalHostname(string $host): void {
		$isLocal = $this->classifier->isLocalHostname($host);

		self::assertTrue($isLocal);
	}

	public function publicHostnamesData(): array {
		return [
			['example.com'],
			['example.net'],
			['example.org'],
			['host.domain'],
			['cloud.domain.tld'],
		];
	}

	/**
	 * @dataProvider publicHostnamesData
	 */
	public function testPublicHostname(string $host): void {
		$isLocal = $this->classifier->isLocalHostname($host);

		self::assertFalse($isLocal);
	}
}
