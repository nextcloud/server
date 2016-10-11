<?php
/**
 * Copyright (c) 2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Http\Client;

use GuzzleHttp\Client as GuzzleClient;
use OC\Http\Client\Client;
use OC\Http\Client\ClientService;
use OCP\ICertificateManager;
use OCP\IConfig;

/**
 * Class ClientServiceTest
 */
class ClientServiceTest extends \Test\TestCase {
	public function testNewClient() {
		$config = $this->createMock(IConfig::class);
		$certificateManager = $this->createMock(ICertificateManager::class);

		$expected = new Client($config, $certificateManager, new GuzzleClient());
		$clientService = new ClientService($config, $certificateManager);
		$this->assertEquals($expected, $clientService->newClient());
	}
}
