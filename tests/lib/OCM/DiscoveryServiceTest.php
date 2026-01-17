<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\OCM;

use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\OCM\OCMDiscoveryService;
use OCA\CloudFederationAPI\Controller\OCMRequestController;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\OCM\Events\LocalOCMDiscoveryEvent;
use OCP\OCM\Events\OCMEndpointRequestEvent;
use OCP\Server;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Test\OCM\Listeners\LocalOCMDiscoveryTestEvent;
use Test\OCM\Listeners\OCMEndpointRequestTestEvent;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class DiscoveryServiceTest extends TestCase {
	private LoggerInterface $logger;
	private RegistrationContext $context;
	private IEventDispatcher $dispatcher;
	private OCMDiscoveryService $discoveryService;
	private IConfig $config;
	private OCMRequestController $requestController;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->context = Server::get(RegistrationContext::class);
		$this->dispatcher = Server::get(IEventDispatcher::class);
		$this->discoveryService = Server::get(OCMDiscoveryService::class);
		$this->config = Server::get(IConfig::class);

		// reset $localProvider value between tests
		$reflection = new ReflectionClass($this->discoveryService);
		$localProvider = $reflection->getProperty('localProvider');
		$localProvider->setValue($this->discoveryService, null);

		$this->requestController = Server::get(OCMRequestController::class);
	}

	public static function dataTestOCMRequest(): array {
		return [
			['/inexistant-path/', 404, null],
			['/ocm-capability-test/', 404, null],
			['/ocm-capability-test/get', 200,
				[
					'capability' => 'ocm-capability-test',
					'path' => '/get',
					'args' => ['get'],
					'totalArgs' => 1,
					'typedArgs' => ['get'],
				]
			],
			['/ocm-capability-test/get/10/', 200,
				[
					'capability' => 'ocm-capability-test',
					'path' => '/get/10',
					'args' => ['get', '10'],
					'totalArgs' => 2,
					'typedArgs' => ['get', '10'],
				]
			],
			['/ocm-capability-test/get/random/10/', 200,
				[
					'capability' => 'ocm-capability-test',
					'path' => '/get/random/10',
					'args' => ['get', 'random', '10'],
					'totalArgs' => 3,
					'typedArgs' => ['get', 'random', 10],
				]
			],
			['/ocm-capability-test/get/random/10/1', 200,
				[
					'capability' => 'ocm-capability-test',
					'path' => '/get/random/10/1',
					'args' => ['get', 'random', '10', '1'],
					'totalArgs' => 4,
					'typedArgs' => ['get', 'random', 10, true],
				]
			],
			['/ocm-capability-test/get/random/10/true', 200,
				[
					'capability' => 'ocm-capability-test',
					'path' => '/get/random/10/true',
					'args' => ['get', 'random', '10', 'true'],
					'totalArgs' => 4,
					'typedArgs' => ['get', 'random', 10, true],
				]
			],
			['/ocm-capability-test/get/random/10/true/42', 200,
				[
					'capability' => 'ocm-capability-test',
					'path' => '/get/random/10/true/42',
					'args' => ['get', 'random', '10', 'true', '42'],
					'totalArgs' => 5,
					'typedArgs' => ['get', 'random', 10, true, 42],
				]
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTestOCMRequest')]
	public function testOCMRequest(string $path, int $expectedStatus, ?array $expectedResult): void {
		$this->context->for('ocm-request-app')->registerEventListener(OCMEndpointRequestEvent::class, OCMEndpointRequestTestEvent::class);
		$this->context->delegateEventListenerRegistrations($this->dispatcher);

		$response = $this->requestController->manageOCMRequests($path);
		$this->assertSame($expectedStatus, $response->getStatus());
		if ($expectedResult !== null) {
			$this->assertSame($expectedResult, $response->getData());
		}
	}


	public function testLocalBaseCapability(): void {
		$local = $this->discoveryService->getLocalOCMProvider();
		$this->assertEmpty(array_diff(['notifications', 'shares'], $local->getCapabilities()));
	}


	public function testLocalAddedCapability(): void {
		$this->context->for('ocm-capability-app')->registerEventListener(LocalOCMDiscoveryEvent::class, LocalOCMDiscoveryTestEvent::class);
		$this->context->delegateEventListenerRegistrations($this->dispatcher);
		$local = $this->discoveryService->getLocalOCMProvider();
		$this->assertEmpty(array_diff(['notifications', 'shares', 'ocm-capability-test'], $local->getCapabilities()));
	}

}
