<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\Mail\Provider;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\Mail\Provider\Manager;
use OCP\Mail\Provider\Address;
use OCP\Mail\Provider\IProvider;
use OCP\Mail\Provider\IService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ManagerTest extends TestCase {

	/** @var Coordinator&MockObject */
	private Coordinator $coordinator;
	/** @var ContainerInterface&MockObject */
	private ContainerInterface $container;
	/** @var LoggerInterface&MockObject */
	private LoggerInterface $logger;
	/** @var IProvider&MockObject */
	private IProvider $provider;
	/** @var IService&MockObject */
	private IService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);

		// construct service registration
		$registration = $this->createMock(ServiceRegistration::class);
		$registration
			->method('getService')
			->willReturn('Mock\Provider\MailProvider');
		// construct registration context
		$context = $this->createMock(RegistrationContext::class);
		$context
			->method('getMailProviders')
			->willReturn([$registration]);
		// construct coordinator
		$this->coordinator = $this->createMock(Coordinator::class);
		$this->coordinator
			->method('getRegistrationContext')
			->willReturn($context);

		// construct mail service
		$this->service = $this->createMock(IService::class);
		$this->service
			->method('id')
			->willReturn('100');
		$this->service
			->method('getLabel')
			->willReturn('Mock Mail Service');
		$this->service
			->method('getPrimaryAddress')
			->willReturn((new Address('user1@testing.com', 'User One')));
		// construct mail provider
		$this->provider = $this->createMock(IProvider::class);
		$this->provider
			->method('id')
			->willReturn('mock-provider');
		$this->provider
			->method('label')
			->willReturn('Mock Provider');
		$this->provider
			->method('listServices')
			->willReturnMap([
				['user0', []],
				['user1', [$this->service->id() => $this->service]]
			]);
		$this->provider
			->method('findServiceById')
			->willReturnMap([
				['user0', '100', null],
				['user1', '100', $this->service]
			]);
		$this->provider
			->method('findServiceByAddress')
			->willReturnMap([
				['user0', 'user0@testing.com', null],
				['user1', 'user1@testing.com', $this->service]
			]);
		// construct container interface
		$this->container = $this->createMock(ContainerInterface::class);
		$this->container
			->method('get')
			->willReturnMap([
				['Mock\Provider\MailProvider', $this->provider]
			]);

	}

	public function testHas(): void {

		// construct mail manager
		$manager = new Manager($this->coordinator, $this->container, $this->logger);
		// test result with providers found
		$this->assertTrue($manager->has());

	}

	public function testCount(): void {

		// construct mail manager
		$manager = new Manager($this->coordinator, $this->container, $this->logger);
		// test result with providers found
		$this->assertGreaterThan(0, $manager->count());

	}

	public function testTypes(): void {

		// construct mail manager
		$manager = new Manager($this->coordinator, $this->container, $this->logger);
		// test result with providers found
		$this->assertEquals(['mock-provider' => 'Mock Provider'], $manager->types());

	}

	public function testProviders(): void {

		// construct mail manager
		$manager = new Manager($this->coordinator, $this->container, $this->logger);
		// test result with providers found
		$this->assertEquals([$this->provider->id() => $this->provider], $manager->providers());

	}

	public function testFindProviderById(): void {

		// construct mail manager
		$manager = new Manager($this->coordinator, $this->container, $this->logger);
		// test result with providers found
		$this->assertEquals($this->provider, $manager->findProviderById($this->provider->id()));

	}

	public function testServices(): void {

		// construct mail manager
		$manager = new Manager($this->coordinator, $this->container, $this->logger);
		// test result with no services found
		$this->assertEquals([], $manager->services('user0'));
		// test result with services found
		$this->assertEquals([$this->provider->id() => [$this->service->id() => $this->service]], $manager->services('user1'));

	}

	public function testFindServiceById(): void {

		// construct mail manager
		$manager = new Manager($this->coordinator, $this->container, $this->logger);
		// test result with no services found and not provider specified
		$this->assertEquals(null, $manager->findServiceById('user0', '100'));
		// test result with no services found and provider specified
		$this->assertEquals(null, $manager->findServiceById('user0', '100', $this->provider->id()));
		// test result with services found and not provider specified
		$this->assertEquals($this->service, $manager->findServiceById('user1', '100'));
		// test result with services found and provider specified
		$this->assertEquals($this->service, $manager->findServiceById('user1', '100', $this->provider->id()));

	}

	public function testFindServiceByAddress(): void {

		// construct mail manager
		$manager = new Manager($this->coordinator, $this->container, $this->logger);
		// test result with no services found and not provider specified
		$this->assertEquals(null, $manager->findServiceByAddress('user0', 'user0@testing.com'));
		// test result with no services found and provider specified
		$this->assertEquals(null, $manager->findServiceByAddress('user0', 'user0@testing.com', $this->provider->id()));
		// test result with services found and not provider specified
		$this->assertEquals($this->service, $manager->findServiceByAddress('user1', 'user1@testing.com'));
		// test result with services found and provider specified
		$this->assertEquals($this->service, $manager->findServiceByAddress('user1', 'user1@testing.com', $this->provider->id()));

	}

}
