<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Stream;

use OC\Files\Stream\HashWrapper;
use Test\TestCase;

class HashWrapperTest extends TestCase {
	/**
	 * @dataProvider hashProvider
	 */
	public function testHashStream($data, string $algo, string $hash): void {
		if (!is_resource($data)) {
			$tmpData = fopen('php://temp', 'r+');
			if ($data !== null) {
				fwrite($tmpData, $data);
				rewind($tmpData);
			}
			$data = $tmpData;
		}

		$wrapper = HashWrapper::wrap($data, $algo, function ($result) use ($hash): void {
			$this->assertEquals($hash, $result);
		});
		stream_get_contents($wrapper);
	}

	public static function hashProvider(): array {
		return [
			['foo', 'md5', 'acbd18db4cc2f85cedef654fccc4a4d8'],
			['foo', 'sha1', '0beec7b5ea3f0fdbc95d0dd47f3c5bc275da8a33'],
			['foo', 'sha256', '2c26b46b68ffc68ff99b453c1d30413413422d706483bfa0f98a5e886266e7ae'],
			[str_repeat('foo', 8192), 'md5', '96684d2b796a2c54a026b5d60f9de819'],
		];
	}
}
