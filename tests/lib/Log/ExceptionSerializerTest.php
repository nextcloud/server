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
	private $serializer;

	public function setUp(): void {
		parent::setUp();

		$config = $this->createMock(SystemConfig::class);
		$this->serializer = new ExceptionSerializer($config);
	}

	private function emit($arguments) {
		\call_user_func_array([$this, 'bind'], $arguments);
	}

	private function bind(array &$myValues): void {
		throw new \Exception('my exception');
	}

	/**
	 * this test ensures that the serializer does not overwrite referenced
	 * variables. It is crafted after a scenario we experienced: the DAV server
	 * emitting the "validateTokens" event, of which later on a handled
	 * exception was passed to the logger. The token was replaced, the original
	 * variable overwritten.
	 */
	public function testSerializer() {
		try {
			$secret = ['Secret'];
			$this->emit([&$secret]);
		} catch (\Exception $e) {
			$this->serializer->serializeException($e);
			$this->assertSame(['Secret'], $secret);
		}
	}
}
