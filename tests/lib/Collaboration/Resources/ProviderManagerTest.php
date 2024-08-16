<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Collaboration\Resources;

use OC\Collaboration\Resources\ProviderManager;
use OCA\Files\Collaboration\Resources\ResourceProvider;
use OCP\AppFramework\QueryException;
use OCP\Collaboration\Resources\IProviderManager;
use OCP\IServerContainer;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ProviderManagerTest extends TestCase {
	/** @var IServerContainer */
	protected $serverContainer;
	/** @var LoggerInterface */
	protected $logger;
	/** @var IProviderManager */
	protected $providerManager;

	protected function setUp(): void {
		parent::setUp();

		$this->serverContainer = $this->createMock(IServerContainer::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->providerManager = new class($this->serverContainer, $this->logger) extends ProviderManager {
			public function countProviders(): int {
				return count($this->providers);
			}
		};
	}

	public function testRegisterResourceProvider(): void {
		$this->providerManager->registerResourceProvider('AwesomeResourceProvider');
		$this->assertSame(1, $this->providerManager->countProviders());
	}

	public function testGetResourceProvidersNoProvider(): void {
		$this->assertCount(0, $this->providerManager->getResourceProviders());
	}

	public function testGetResourceProvidersValidProvider(): void {
		$this->serverContainer->expects($this->once())
			->method('query')
			->with($this->equalTo(ResourceProvider::class))
			->willReturn($this->createMock(ResourceProvider::class));

		$this->providerManager->registerResourceProvider(ResourceProvider::class);
		$resourceProviders = $this->providerManager->getResourceProviders();

		$this->assertCount(1, $resourceProviders);
		$this->assertInstanceOf(ResourceProvider::class, $resourceProviders[0]);
	}

	public function testGetResourceProvidersInvalidProvider(): void {
		$this->serverContainer->expects($this->once())
			->method('query')
			->with($this->equalTo('InvalidResourceProvider'))
			->willThrowException(new QueryException('A meaningful error message'));

		$this->logger->expects($this->once())
			->method('error');

		$this->providerManager->registerResourceProvider('InvalidResourceProvider');
		$resourceProviders = $this->providerManager->getResourceProviders();

		$this->assertCount(0, $resourceProviders);
	}

	public function testGetResourceProvidersValidAndInvalidProvider(): void {
		$this->serverContainer->expects($this->exactly(2))
			->method('query')
			->withConsecutive(
				[$this->equalTo('InvalidResourceProvider')],
				[$this->equalTo(ResourceProvider::class)],
			)->willReturnOnConsecutiveCalls(
				$this->throwException(new QueryException('A meaningful error message')),
				$this->createMock(ResourceProvider::class),
			);

		$this->logger->expects($this->once())
			->method('error');

		$this->providerManager->registerResourceProvider('InvalidResourceProvider');
		$this->providerManager->registerResourceProvider(ResourceProvider::class);
		$resourceProviders = $this->providerManager->getResourceProviders();

		$this->assertCount(1, $resourceProviders);
	}
}
