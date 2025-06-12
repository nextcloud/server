<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\Azure;
use OCP\IConfig;
use OCP\Server;

/**
 * @group PRIMARY-azure
 */
class AzureTest extends ObjectStoreTestCase {
	protected function getInstance() {
		$config = Server::get(IConfig::class)->getSystemValue('objectstore');
		if (!is_array($config) || $config['class'] !== 'OC\\Files\\ObjectStore\\Azure') {
			$this->markTestSkipped('objectstore not configured for azure');
		}

		return new Azure($config['arguments']);
	}

	public function testFseekSize(): void {
		$this->markTestSkipped('azure does not support seeking at the moment');
	}
}
