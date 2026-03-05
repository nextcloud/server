<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\Swift;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\IConfig;
use OCP\Server;

#[\PHPUnit\Framework\Attributes\Group('PRIMARY-swift')]
class SwiftTest extends ObjectStoreTestCase {
	/**
	 * @return IObjectStore
	 */
	protected function getInstance() {
		$config = Server::get(IConfig::class)->getSystemValue('objectstore');
		if (!is_array($config) || $config['class'] !== 'OC\\Files\\ObjectStore\\Swift') {
			$this->markTestSkipped('objectstore not configured for swift');
		}

		return new Swift($config['arguments']);
	}

	public function testFseekSize(): void {
		$this->markTestSkipped('Swift does not support seeking at the moment');
	}
}
