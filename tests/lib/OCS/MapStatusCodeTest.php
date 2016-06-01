<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test\OCS;

use OCP\AppFramework\Http;

class MapStatusCodeTest extends \Test\TestCase {

	/**
	 * @dataProvider providesStatusCodes
	 */
	public function testStatusCodeMapper($expected, $sc) {
		$result = \OC_API::mapStatusCodes($sc);
		$this->assertEquals($expected, $result);
	}

	public function providesStatusCodes() {
		return [
			[Http::STATUS_OK, 100],
			[Http::STATUS_BAD_REQUEST, 104],
			[Http::STATUS_BAD_REQUEST, 1000],
			[201, 201],
		];
	}
}
