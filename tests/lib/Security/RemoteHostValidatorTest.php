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
use Test\TestCase;

class RemoteHostValidatorTest extends TestCase {
	private RemoteHostValidator $validator;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->validator = $this->createInstanceWithMocks(RemoteHostValidator::class);
	}

	public static function dataValid(): array {
		return [
			['nextcloud.com', true],
			['com.one-.nextcloud-one.com', false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataValid')]
	public function testValid(string $host, bool $expected): void {
		$this->mocks[HostnameClassifier::class]
			->method('isLocalHostname')
			->with($host)
			->willReturn(false);
		$this->mocks[IpAddressClassifier::class]
			->method('isLocalAddress')
			->with($host)
			->willReturn(false);

		$valid = $this->validator->isValid($host);

		self::assertSame($expected, $valid);
	}

	public function testLocalHostname(): void {
		$host = 'localhost';
		$this->mocks[HostnameClassifier::class]
			->method('isLocalHostname')
			->with($host)
			->willReturn(true);
		$this->mocks[IpAddressClassifier::class]
			->method('isLocalAddress')
			->with($host)
			->willReturn(false);

		$valid = $this->validator->isValid($host);

		self::assertFalse($valid);
	}

	public function testLocalAddress(): void {
		$host = '10.0.0.10';
		$this->mocks[HostnameClassifier::class]
			->method('isLocalHostname')
			->with($host)
			->willReturn(false);
		$this->mocks[IpAddressClassifier::class]
			->method('isLocalAddress')
			->with($host)
			->willReturn(true);

		$valid = $this->validator->isValid($host);

		self::assertFalse($valid);
	}
}
