<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test;

use OC\App\AppStore\Fetcher\AppFetcher;
use OC\Config;
use OC\Server;
use OCP\Comments\ICommentsManager;

/**
 * Class Server
 *
 * @group DB
 *
 * @package Test
 */
class ServerTest extends \Test\TestCase {
	/** @var \OC\Server */
	protected $server;


	protected function setUp(): void {
		parent::setUp();
		$config = new Config(\OC::$configDir);
		$this->server = new Server('', $config);
	}

	public static function dataTestQuery(): array {
		return [
			['\OCP\Activity\IManager', '\OC\Activity\Manager'],
			['\OCP\IConfig', '\OC\AllConfig'],
			['\OCP\IAppConfig', '\OC\AppConfig'],
			[AppFetcher::class, AppFetcher::class],
			['\OCP\App\IAppManager', '\OC\App\AppManager'],
			['\OCP\Command\IBus', '\OC\Command\AsyncBus'],
			['\OCP\IAvatarManager', '\OC\Avatar\AvatarManager'],
		];
	}

	/**
	 * @dataProvider dataTestQuery
	 *
	 * @param string $serviceName
	 * @param string $instanceOf
	 */
	public function testQuery($serviceName, $instanceOf): void {
		$this->assertInstanceOf($instanceOf, $this->server->query($serviceName), 'Service "' . $serviceName . '"" did not return the right class');
	}

	public function testGetCertificateManager(): void {
		$this->assertInstanceOf('\OC\Security\CertificateManager', $this->server->getCertificateManager(), 'service returned by "getCertificateManager" did not return the right class');
		$this->assertInstanceOf('\OCP\ICertificateManager', $this->server->getCertificateManager(), 'service returned by "getCertificateManager" did not return the right class');
	}

	public function testOverwriteDefaultCommentsManager(): void {
		$config = $this->server->getConfig();
		$defaultManagerFactory = $config->getSystemValue('comments.managerFactory', '\OC\Comments\ManagerFactory');

		$config->setSystemValue('comments.managerFactory', '\Test\Comments\FakeFactory');

		$manager = $this->server->get(ICommentsManager::class);
		$this->assertInstanceOf('\OCP\Comments\ICommentsManager', $manager);

		$config->setSystemValue('comments.managerFactory', $defaultManagerFactory);
	}
}
