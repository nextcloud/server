<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\PublicNamespace;

class UtilTest extends \Test\TestCase {
	/**
	 * @dataProvider channelProvider
	 *
	 * @param string $channel
	 */
	public function testOverrideChannel($channel) {
		\OCP\Util::setChannel($channel);
		$actual = \OCP\Util::getChannel($channel);
		$this->assertEquals($channel, $actual);
	}
	
	public function channelProvider() {
		return [
			['daily'],
			['beta'],
			['stable'],
			['production']
		];
	}
}
