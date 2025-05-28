<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Tests\Service;

use DateTimeImmutable;
use OCA\User_LDAP\Service\BirthdateParserService;
use PHPUnit\Framework\TestCase;

class BirthdateParserServiceTest extends TestCase {
	private BirthdateParserService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->service = new BirthdateParserService();
	}

	public static function parseBirthdateDataProvider(): array {
		return [
			['2024-01-01', new DateTimeImmutable('2024-01-01'), false],
			['20240101', new DateTimeImmutable('2024-01-01'), false],
			['199412161032Z', new DateTimeImmutable('1994-12-16'), false], // LDAP generalized time
			['199412160532-0500', new DateTimeImmutable('1994-12-16'), false], // LDAP generalized time
			['2023-07-31T00:60:59.000Z', null, true],
			['01.01.2024', null, true],
			['01/01/2024', null, true],
			['01 01 2024', null, true],
			['foobar', null, true],
		];
	}

	/**
	 * @dataProvider parseBirthdateDataProvider
	 */
	public function testParseBirthdate(
		string $value,
		?DateTimeImmutable $expected,
		bool $shouldThrow,
	): void {
		if ($shouldThrow) {
			$this->expectException(\InvalidArgumentException::class);
		}

		$actual = $this->service->parseBirthdate($value);
		$this->assertEquals($expected, $actual);
	}
}
