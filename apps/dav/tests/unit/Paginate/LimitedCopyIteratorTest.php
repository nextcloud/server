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

namespace OCA\DAV\Tests\Paginate;

use OCA\DAV\Paginate\LimitedCopyIterator;
use Test\TestCase;

class LimitedCopyIteratorTest extends TestCase {
	public function testBasic() {
		$data = range(0, 100);

		$iterator = new LimitedCopyIterator(new \ArrayIterator($data), 10);

		$this->assertEquals(array_slice($data, 0, 10), iterator_to_array($iterator->getFirstItems()));
		$results = iterator_to_array($iterator);
		$this->assertEquals($data, $results);
		$this->assertEquals(array_slice($data, 0, 10), iterator_to_array($iterator->getFirstItems()));
	}
}