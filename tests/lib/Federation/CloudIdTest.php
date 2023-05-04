<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
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

namespace Test\Federation;

use OC\Federation\CloudId;
use Test\TestCase;

class CloudIdTest extends TestCase {
	public function dataGetDisplayCloudId() {
		return [
			['test@example.com', 'test@example.com'],
			['test@http://example.com', 'test@example.com'],
			['test@https://example.com', 'test@example.com'],
		];
	}

	/**
	 * @dataProvider dataGetDisplayCloudId
	 *
	 * @param string $id
	 * @param string $display
	 */
	public function testGetDisplayCloudId($id, $display) {
		$cloudId = new CloudId($id, '', '');
		$this->assertEquals($display, $cloudId->getDisplayId());
	}
}
