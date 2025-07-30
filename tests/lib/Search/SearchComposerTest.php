<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Search;

use InvalidArgumentException;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\Search\SearchComposer;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IInAppSearch;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
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

		$this->setupUrlGenerator();
	}

	private function setupUrlGenerator(): void {
		$this->urlGenerator->method('imagePath')
			->willReturnCallback(function ($appId, $imageName) {
				return "/apps/$appId/img/$imageName";
			});
	}

	private function setupEmptyRegistrationContext(): void {
		$this->bootstrapCoordinator->expects($this->once())
			->method('getRegistrationContext')
			->willReturn(null);
	}

	private function setupAppConfigForAllowedProviders(array $allowedProviders = []): void {
		$this->appConfig->method('getValueArray')
			->with('core', 'unified_search.providers_allowed')
			->willReturn($allowedProviders);
	}

	/**
	 * @param array<string, array{service: string, appId: string, order: int, isInApp?: bool}> $providerConfigs
	 * @return array{registrations: ServiceRegistration[], providers: IProvider[]}
	 */
	private function createMockProvidersAndRegistrations(array $providerConfigs): array {
		$registrations = [];
		$providers = [];
		$containerMap = [];

		foreach ($providerConfigs as $providerId => $config) {
			// Create registration mock
			$registration = $this->createMock(ServiceRegistration::class);
			$registration->method('getService')->willReturn($config['service']);
			$registration->method('getAppId')->willReturn($config['appId']);
			$registrations[] = $registration;

			// Create provider mock
			$providerClass = $config['isInApp'] ?? false ? IInAppSearch::class : IProvider::class;
			$provider = $this->createMock($providerClass);
			$provider->method('getId')->willReturn($providerId);
			$provider->method('getName')->willReturn("Provider $providerId");
			$provider->method('getOrder')->willReturn($config['order']);

			$providers[$providerId] = $provider;
			$containerMap[] = [$config['service'], $provider];
		}

		$this->container->expects($this->exactly(count($providerConfigs)))
			->method('get')
			->willReturnMap($containerMap);

		return ['registrations' => $registrations, 'providers' => $providers];
	}

	private function setupRegistrationContextWithProviders(array $registrations): void {
		$registrationContext = $this->createMock(RegistrationContext::class);
		$registrationContext->method('getSearchProviders')->willReturn($registrations);

		$this->bootstrapCoordinator->expects($this->once())
			->method('getRegistrationContext')
			->willReturn($registrationContext);
	}

	public function testGetProvidersWithNoRegisteredProviders(): void {
		$this->setupEmptyRegistrationContext();

		$providers = $this->searchComposer->getProviders('/test/route', []);

		$this->assertIsArray($providers);
		$this->assertEmpty($providers);
	}

	public function testSearchWithUnknownProvider(): void {
		$this->setupEmptyRegistrationContext();

		$user = $this->createMock(IUser::class);
		$query = $this->createMock(ISearchQuery::class);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Provider unknown_provider is unknown');

		$this->searchComposer->search($user, 'unknown_provider', $query);
	}

	public function testGetProvidersWithMultipleProviders(): void {
		$providerConfigs = [
			'provider1' => ['service' => 'provider1_service', 'appId' => 'app1', 'order' => 10],
			'provider2' => ['service' => 'provider2_service', 'appId' => 'app2', 'order' => 5],
			'provider3' => ['service' => 'provider3_service', 'appId' => 'app3', 'order' => 15, 'isInApp' => true],
		];

		$mockData = $this->createMockProvidersAndRegistrations($providerConfigs);
		$this->setupRegistrationContextWithProviders($mockData['registrations']);
		$this->setupAppConfigForAllowedProviders();

		$providers = $this->searchComposer->getProviders('/test/route', []);

		$this->assertProvidersStructureAndSorting($providers, [
			['id' => 'provider2', 'name' => 'Provider provider2', 'appId' => 'app2', 'order' => 5, 'inAppSearch' => false],
			['id' => 'provider1', 'name' => 'Provider provider1', 'appId' => 'app1', 'order' => 10, 'inAppSearch' => false],
			['id' => 'provider3', 'name' => 'Provider provider3', 'appId' => 'app3', 'order' => 15, 'inAppSearch' => true],
		]);
	}

	public function testGetProvidersWithEmptyAllowedProvidersConfiguration(): void {
		$providerConfigs = [
			'provider1' => ['service' => 'provider1_service', 'appId' => 'app1', 'order' => 10],
			'provider2' => ['service' => 'provider2_service', 'appId' => 'app2', 'order' => 5],
		];

		$mockData = $this->createMockProvidersAndRegistrations($providerConfigs);
		$this->setupRegistrationContextWithProviders($mockData['registrations']);
		$this->setupAppConfigForAllowedProviders();

		$providers = $this->searchComposer->getProviders('/test/route', []);

		$this->assertCount(2, $providers);
		$this->assertProvidersAreSortedByOrder($providers);
		$this->assertEquals('provider2', $providers[0]['id']);
		$this->assertEquals('provider1', $providers[1]['id']);
	}

	public function testGetProvidersWithAllowedProvidersRestriction(): void {
		$providerConfigs = [
			'provider1' => ['service' => 'provider1_service', 'appId' => 'app1', 'order' => 10],
			'provider2' => ['service' => 'provider2_service', 'appId' => 'app2', 'order' => 5],
			'provider3' => ['service' => 'provider3_service', 'appId' => 'app3', 'order' => 15],
			'provider4' => ['service' => 'provider4_service', 'appId' => 'app4', 'order' => 8],
		];

		$mockData = $this->createMockProvidersAndRegistrations($providerConfigs);
		$this->setupRegistrationContextWithProviders($mockData['registrations']);
		$this->setupAppConfigForAllowedProviders(['provider1', 'provider3']);

		$providers = $this->searchComposer->getProviders('/test/route', []);

		$this->assertProvidersStructureAndSorting($providers, [
			['id' => 'provider1', 'name' => 'Provider provider1', 'appId' => 'app1', 'order' => 10, 'inAppSearch' => false],
			['id' => 'provider3', 'name' => 'Provider provider3', 'appId' => 'app3', 'order' => 15, 'inAppSearch' => false],
		]);

		// Verify excluded providers are not present
		$providerIds = array_column($providers, 'id');
		$this->assertNotContains('provider2', $providerIds);
		$this->assertNotContains('provider4', $providerIds);
	}

	public function testGetProvidersFiltersByAllowedProvidersCompletely(): void {
		$providerConfigs = [
			'provider1' => ['service' => 'provider1_service', 'appId' => 'app1', 'order' => 10],
			'provider2' => ['service' => 'provider2_service', 'appId' => 'app2', 'order' => 5],
		];

		$mockData = $this->createMockProvidersAndRegistrations($providerConfigs);
		$this->setupRegistrationContextWithProviders($mockData['registrations']);
		$this->setupAppConfigForAllowedProviders(['provider_not_exists']);

		$providers = $this->searchComposer->getProviders('/test/route', []);

		$this->assertIsArray($providers);
		$this->assertEmpty($providers);
	}

	public function testGetProvidersWithMixedOrderValues(): void {
		$providerConfigs = [
			'provider1' => ['service' => 'provider1_service', 'appId' => 'app1', 'order' => 100],
			'provider2' => ['service' => 'provider2_service', 'appId' => 'app2', 'order' => 1],
			'provider3' => ['service' => 'provider3_service', 'appId' => 'app3', 'order' => 50],
		];

		$mockData = $this->createMockProvidersAndRegistrations($providerConfigs);
		$this->setupRegistrationContextWithProviders($mockData['registrations']);
		$this->setupAppConfigForAllowedProviders();

		$providers = $this->searchComposer->getProviders('/test/route', []);

		$this->assertCount(3, $providers);
		$this->assertProvidersAreSortedByOrder($providers);

		// Verify correct ordering: provider2 (1), provider3 (50), provider1 (100)
		$this->assertEquals('provider2', $providers[0]['id']);
		$this->assertEquals('provider3', $providers[1]['id']);
		$this->assertEquals('provider1', $providers[2]['id']);
	}

	public function testProviderIconGeneration(): void {
		$providerConfigs = [
			'provider1' => ['service' => 'provider1_service', 'appId' => 'app1', 'order' => 10],
		];

		$mockData = $this->createMockProvidersAndRegistrations($providerConfigs);
		$this->setupRegistrationContextWithProviders($mockData['registrations']);
		$this->setupAppConfigForAllowedProviders();

		$providers = $this->searchComposer->getProviders('/test/route', []);

		$this->assertCount(1, $providers);
		$this->assertArrayHasKey('icon', $providers[0]);
		$this->assertStringContainsString('/apps/provider1/img/provider1.svg', $providers[0]['icon']);
	}

	/**
	 * Assert providers array structure and expected sorting
	 */
	private function assertProvidersStructureAndSorting(array $actualProviders, array $expectedProviders): void {
		$this->assertIsArray($actualProviders);
		$this->assertCount(count($expectedProviders), $actualProviders);

		foreach ($actualProviders as $index => $provider) {
			$this->assertProviderHasRequiredFields($provider);

			$expected = $expectedProviders[$index];
			$this->assertEquals($expected['id'], $provider['id']);
			$this->assertEquals($expected['name'], $provider['name']);
			$this->assertEquals($expected['appId'], $provider['appId']);
			$this->assertEquals($expected['order'], $provider['order']);
			$this->assertEquals($expected['inAppSearch'], $provider['inAppSearch']);
		}

		$this->assertProvidersAreSortedByOrder($actualProviders);
	}

	private function assertProviderHasRequiredFields(array $provider): void {
		$requiredFields = ['id', 'appId', 'name', 'icon', 'order', 'triggers', 'filters', 'inAppSearch'];
		foreach ($requiredFields as $field) {
			$this->assertArrayHasKey($field, $provider, "Provider must have '$field' field");
		}
	}

	private function assertProvidersAreSortedByOrder(array $providers): void {
		$orders = array_column($providers, 'order');
		$sortedOrders = $orders;
		sort($sortedOrders);
		$this->assertEquals($sortedOrders, $orders, 'Providers should be sorted by order');
	}
}
