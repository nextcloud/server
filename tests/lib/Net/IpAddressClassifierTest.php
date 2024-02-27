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

use OC\Net\IpAddressClassifier;
use Test\TestCase;

class IpAddressClassifierTest extends TestCase {
	private IpAddressClassifier $classifier;

	protected function setUp(): void {
		parent::setUp();

		$this->classifier = new IpAddressClassifier();
	}

	public function publicIpAddressData(): array {
		return [
			['8.8.8.8'],
			['8.8.4.4'],
			['2001:4860:4860::8888'],
			['2001:4860:4860::8844'],
		];
	}

	/**
	 * @dataProvider publicIpAddressData
	 */
	public function testPublicAddress(string $ip): void {
		$isLocal = $this->classifier->isLocalAddress($ip);

		self::assertFalse($isLocal);
	}

	public function localIpAddressData(): array {
		return [
			['192.168.0.1'],
			['fe80::200:5aee:feaa:20a2'],
			['0:0:0:0:0:ffff:10.0.0.1'],
			['0:0:0:0:0:ffff:127.0.0.0'],
			['10.0.0.1'],
			['::'],
			['::1'],
			['100.100.100.200'],
			['192.0.0.1'],
		];
	}

	/**
	 * @dataProvider localIpAddressData
	 */
	public function testLocalAddress(string $ip): void {
		$isLocal = $this->classifier->isLocalAddress($ip);

		self::assertTrue($isLocal);
	}
}
