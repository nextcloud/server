<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Search;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\Search\SearchComposer;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class SearchComposerTest extends TestCase {
	private Coordinator&MockObject $bootstrapCoordinator;
	private ContainerInterface&MockObject $container;
	private IURLGenerator&MockObject $urlGenerator;
	private LoggerInterface&MockObject $logger;
	private IAppConfig&MockObject $appConfig;
	private SearchComposer $searchComposer;

	protected function setUp(): void {
		parent::setUp();

		$this->bootstrapCoordinator = $this->createMock(Coordinator::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->searchComposer = new SearchComposer(
			$this->bootstrapCoordinator,
			$this->container,
			$this->urlGenerator,
			$this->logger,
			$this->appConfig
		);
	}

	private function setupEmptyRegistrationContext(): void {
		$this->bootstrapCoordinator->expects($this->once())
			->method('getRegistrationContext')
			->willReturn(null);
	}

	public function testGetProvidersWithNoRegisteredProviders(): void {
		$this->setupEmptyRegistrationContext();

		$providers = $this->searchComposer->getProviders('/test/route', []);

		$this->assertIsArray($providers);
		$this->assertEmpty($providers);
	}
}
