<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

	public static function localHostnamesData(): array {
		return [
			['localhost'],
			['localHost'],
			['random-host'],
			['another-host.local'],
			['service.localhost'],
			['randomdomain.internal'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('localHostnamesData')]
	public function testLocalHostname(string $host): void {
		$isLocal = $this->classifier->isLocalHostname($host);

		self::assertTrue($isLocal);
	}

	public static function publicHostnamesData(): array {
		return [
			['example.com'],
			['example.net'],
			['example.org'],
			['host.domain'],
			['cloud.domain.tld'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('publicHostnamesData')]
	public function testPublicHostname(string $host): void {
		$isLocal = $this->classifier->isLocalHostname($host);

		self::assertFalse($isLocal);
	}
}
