<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace lib\Log;

use OC\Log\ExceptionSerializer;
use OC\SystemConfig;
use Test\TestCase;

class ExceptionSerializerTest extends TestCase {

	private ExceptionSerializer $serializer;

	public function setUp(): void {
		parent::setUp();

		$config = $this->createMock(SystemConfig::class);
		$this->serializer = new ExceptionSerializer($config);
	}

	private function bind(array &$myValues): void {
		throw new \Exception('my exception');
	}

	public function testSerializer() {
		try {
			$secret = ['Secret'];
			$this->bind($secret);
		} catch (\Exception $e) {
			$ne = new \Exception('foobar', 0, $e);
			$result = $this->serializer->serializeException($ne);

			$this->assertSame(['Secret'], $secret);
			var_dump($result);
		}
	}
}
