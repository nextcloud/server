<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	public function testGetDisplayCloudId($id, $display): void {
		$cloudId = new CloudId($id, '', '');
		$this->assertEquals($display, $cloudId->getDisplayId());
	}
}
