<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\AppFramework\Utility;

use OC\AppFramework\Utility\TimeFactory;

class TimeFactoryTest extends \Test\TestCase {
	protected TimeFactory $timeFactory;

	protected function setUp(): void {
		$this->timeFactory = new TimeFactory();
	}

	public function testNow(): void {
		$now = $this->timeFactory->now();
		self::assertSame('UTC', $now->getTimezone()->getName());
	}

	public function testNowWithTimeZone(): void {
		$timezone = new \DateTimeZone('Europe/Berlin');
		$withTimeZone = $this->timeFactory->withTimeZone($timezone);

		$now = $withTimeZone->now();
		self::assertSame('Europe/Berlin', $now->getTimezone()->getName());
	}

	public function testGetTimeZone(): void {
		$expected = new \DateTimeZone('Europe/Berlin');
		$actual = $this->timeFactory->getTimeZone('Europe/Berlin');
		self::assertEquals($expected, $actual);
	}

	public function testGetTimeZoneUTC(): void {
		$expected = new \DateTimeZone('UTC');
		$actual = $this->timeFactory->getTimeZone();
		self::assertEquals($expected, $actual);
	}

	public function testGetTimeZoneInvalid(): void {
		$this->expectException(\Exception::class);
		$this->timeFactory->getTimeZone('blubblub');
	}
}
