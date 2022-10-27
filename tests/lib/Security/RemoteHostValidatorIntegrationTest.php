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

namespace lib\Security;

use OC\Net\HostnameClassifier;
use OC\Net\IpAddressClassifier;
use OC\Security\RemoteHostValidator;
use OCP\IConfig;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Test\TestCase;

class RemoteHostValidatorIntegrationTest extends TestCase {

	/** @var IConfig|IConfig&MockObject|MockObject */
	private IConfig $config;
	private RemoteHostValidator $validator;

	protected function setUp(): void {
		parent::setUp();

		// Mock config to avoid any side effects
		$this->config = $this->createMock(IConfig::class);

		$this->validator = new RemoteHostValidator(
			$this->config,
			Server::get(HostnameClassifier::class),
			Server::get(IpAddressClassifier::class),
			new NullLogger(),
		);
	}

	public function localHostsData(): array {
		return [
			['[::1]'],
			['[::]'],
			['192.168.0.1'],
			['172.16.42.1'],
			['[fdf8:f53b:82e4::53]'],
			['[fe80::200:5aee:feaa:20a2]'],
			['[0:0:0:0:0:ffff:10.0.0.1]'],
			['[0:0:0:0:0:ffff:127.0.0.0]'],
			['10.0.0.1'],
			['!@#$'], // test invalid url
			['100.100.100.200'],
			['192.0.0.1'],
			['0177.0.0.9'],
			['⑯⑨。②⑤④。⑯⑨｡②⑤④'],
			['127。②⑤④。⑯⑨.②⑤④'],
			['127.0.00000000000000000000000000000000001'],
			['127.1'],
			['127.000.001'],
			['0177.0.0.01'],
			['0x7f.0x0.0x0.0x1'],
			['0x7f000001'],
			['2130706433'],
			['00000000000000000000000000000000000000000000000000177.1'],
			['0x7f.1'],
			['127.0x1'],
			['[0000:0000:0000:0000:0000:0000:0000:0001]'],
			['[0:0:0:0:0:0:0:1]'],
			['[0:0:0:0::0:0:1]'],
			['%31%32%37%2E%30%2E%30%2E%31'],
			['%31%32%37%2E%30%2E%30.%31'],
			['[%3A%3A%31]'],
		];
	}

	/**
	 * @dataProvider localHostsData
	 */
	public function testLocalHostsWhenNotAllowed(string $host): void {
		$this->config
			->method('getSystemValueBool')
			->with('allow_local_remote_servers', false)
			->willReturn(false);

		$isValid = $this->validator->isValid($host);

		self::assertFalse($isValid);
	}

	/**
	 * @dataProvider localHostsData
	 */
	public function testLocalHostsWhenAllowed(string $host): void {
		$this->config
			->method('getSystemValueBool')
			->with('allow_local_remote_servers', false)
			->willReturn(true);

		$isValid = $this->validator->isValid($host);

		self::assertTrue($isValid);
	}

	public function externalAddressesData():array {
		return [
			['8.8.8.8'],
			['8.8.4.4'],
			['8.8.8.8'],
			['8.8.4.4'],
			['[2001:4860:4860::8888]'],
		];
	}

	/**
	 * @dataProvider externalAddressesData
	 */
	public function testExternalHost(string $host): void {
		$this->config
			->method('getSystemValueBool')
			->with('allow_local_remote_servers', false)
			->willReturn(false);

		$isValid = $this->validator->isValid($host);

		self::assertTrue($isValid);
	}
}
