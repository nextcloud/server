<?php
/**
 * SPDX-FileCopyrightText: 2023-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
