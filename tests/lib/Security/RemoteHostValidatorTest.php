<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
