<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Collaboration\Resources;

use OC\Collaboration\Resources\Manager;
use OCP\Collaboration\Resources\IManager;
use OCP\Collaboration\Resources\IProviderManager;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ManagerTest extends TestCase {
	/** @var LoggerInterface */
	protected $logger;
	/** @var IProviderManager */
	protected $providerManager;
	/** @var IManager */
	protected $manager;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->providerManager = $this->createMock(IProviderManager::class);

		/** @var IDBConnection $connection */
		$connection = $this->createMock(IDBConnection::class);
		$this->manager = new Manager($connection, $this->providerManager, $this->logger);
	}

	public function testRegisterResourceProvider(): void {
		$this->logger->expects($this->once())
			->method('debug')
			->with($this->equalTo('\OC\Collaboration\Resources\Manager::registerResourceProvider is deprecated'), $this->equalTo(['provider' => 'AwesomeResourceProvider']));
		$this->providerManager->expects($this->once())
			->method('registerResourceProvider')
			->with($this->equalTo('AwesomeResourceProvider'));

		$this->manager->registerResourceProvider('AwesomeResourceProvider');
	}
}
