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
	public function testNewClient(): void {
		/** @var IConfig $config */
		$config = $this->createMock(IConfig::class);
		/** @var ICertificateManager $certificateManager */
		$certificateManager = $this->createMock(ICertificateManager::class);

		$clientService = new ClientService($config, $certificateManager);
		$this->assertEquals(
			new Client($config, $certificateManager, new GuzzleClient()),
			$clientService->newClient()
		);
	}
}
