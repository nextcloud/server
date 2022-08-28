<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Robin Appelman <robin@icewind.nl>
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
 *
 */

namespace Test\Files\Stream;

use OC\Files\Stream\HashWrapper;
use Test\TestCase;

class HashWrapperTest extends TestCase {
	/**
	 * @dataProvider hashProvider
	 */
	public function testHashStream($data, string $algo, string $hash) {
		if (!is_resource($data)) {
			$tmpData = fopen('php://temp', 'r+');
			if ($data !== null) {
				fwrite($tmpData, $data);
				rewind($tmpData);
			}
			$data = $tmpData;
		}

		$wrapper = HashWrapper::wrap($data, $algo, function ($result) use ($hash) {
			$this->assertEquals($hash, $result);
		});
		stream_get_contents($wrapper);
	}

	public function hashProvider() {
		return [
			['foo', 'md5', 'acbd18db4cc2f85cedef654fccc4a4d8'],
			['foo', 'sha1', '0beec7b5ea3f0fdbc95d0dd47f3c5bc275da8a33'],
			['foo', 'sha256', '2c26b46b68ffc68ff99b453c1d30413413422d706483bfa0f98a5e886266e7ae'],
			[str_repeat('foo', 8192), 'md5', '96684d2b796a2c54a026b5d60f9de819'],
		];
	}
}
