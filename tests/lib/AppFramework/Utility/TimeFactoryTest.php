<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022, Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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
}
