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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class RemoteHostValidatorTest extends TestCase {
	/** @var IConfig|IConfig&MockObject|MockObject */
	private IConfig $config;
	/** @var HostnameClassifier|HostnameClassifier&MockObject|MockObject */
	private HostnameClassifier $hostnameClassifier;
	/** @var IpAddressClassifier|IpAddressClassifier&MockObject|MockObject */
	private IpAddressClassifier $ipAddressClassifier;
	/** @var MockObject|LoggerInterface|LoggerInterface&MockObject */
	private LoggerInterface $logger;
	private RemoteHostValidator $validator;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->hostnameClassifier = $this->createMock(HostnameClassifier::class);
		$this->ipAddressClassifier = $this->createMock(IpAddressClassifier::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->validator = new RemoteHostValidator(
			$this->config,
			$this->hostnameClassifier,
			$this->ipAddressClassifier,
			$this->logger,
		);
	}

	public function dataValid(): array {
		return [
			['nextcloud.com', true],
			['com.one-.nextcloud-one.com', false],
		];
	}

	/**
	 * @dataProvider dataValid
	 */
	public function testValid(string $host, bool $expected): void {
		$this->hostnameClassifier
			->method('isLocalHostname')
			->with($host)
			->willReturn(false);
		$this->ipAddressClassifier
			->method('isLocalAddress')
			->with($host)
			->willReturn(false);

		$valid = $this->validator->isValid($host);

		self::assertSame($expected, $valid);
	}

	public function testLocalHostname(): void {
		$host = 'localhost';
		$this->hostnameClassifier
			->method('isLocalHostname')
			->with($host)
			->willReturn(true);
		$this->ipAddressClassifier
			->method('isLocalAddress')
			->with($host)
			->willReturn(false);

		$valid = $this->validator->isValid($host);

		self::assertFalse($valid);
	}

	public function testLocalAddress(): void {
		$host = '10.0.0.10';
		$this->hostnameClassifier
			->method('isLocalHostname')
			->with($host)
			->willReturn(false);
		$this->ipAddressClassifier
			->method('isLocalAddress')
			->with($host)
			->willReturn(true);

		$valid = $this->validator->isValid($host);

		self::assertFalse($valid);
	}
}
