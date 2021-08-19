<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
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

namespace Icewind\Streams\Tests;

use Icewind\Streams\CountWrapper;

class CountWrapperTest extends WrapperTest {
	protected function wrapSource($source, $callback = null) {
		if (is_null($callback)) {
			$callback = function () {
			};
		}
		return CountWrapper::wrap($source, $callback);
	}

	public function testReadCount() {
		$count = 0;

		$source = fopen('php://temp', 'r+');
		fwrite($source, 'foobar');
		rewind($source);

		$wrapped = CountWrapper::wrap($source, function ($readCount) use (&$count) {
			$count = $readCount;
		});

		stream_get_contents($wrapped);
		fclose($wrapped);
		$this->assertSame(6, $count);
	}

	public function testWriteCount() {
		$count = 0;

		$source = fopen('php://temp', 'r+');

		$wrapped = CountWrapper::wrap($source, function ($readCount, $writeCount) use (&$count) {
			$count = $writeCount;
		});

		fwrite($wrapped, 'foobar');
		fclose($wrapped);
		$this->assertSame(6, $count);
	}
}
