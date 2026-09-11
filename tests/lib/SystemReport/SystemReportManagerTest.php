<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\SystemReport;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\SystemReport\SystemReportManager;
use OCP\SystemReport\ISystemReportSection;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

final class SystemReportManagerTest extends TestCase {
	private Coordinator&MockObject $coordinator;

	private ContainerInterface&MockObject $container;

	private LoggerInterface&MockObject $logger;

	private SystemReportManager $manager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->coordinator = $this->createMock(Coordinator::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->manager = new SystemReportManager(
			$this->coordinator,
			$this->container,
			$this->logger,
		);
	}

	/**
	 * @param \OC\AppFramework\Bootstrap\ServiceRegistration[] $registrations
	 */
	private function withRegisteredSections(array $registrations): void {
		$context = $this->createMock(RegistrationContext::class);
		$context->expects(self::atLeastOnce())
			->method('getSystemReportSections')
			->willReturn($registrations);

		$this->coordinator->expects(self::atLeastOnce())
			->method('getRegistrationContext')
			->willReturn($context);
	}

	public function testGetSectionsReturnsNoneWhenNoneRegistered(): void {
		$this->withRegisteredSections([]);

		$this->assertSame([], $this->manager->getSections());
	}

	public function testGetSectionsResolvesAndReturnsRegisteredSections(): void {
		$section = $this->createMock(ISystemReportSection::class);
		$section->expects(self::once())
			->method('getDetails')
			->willReturn([]);

		$this->container->expects(self::once())
			->method('get')
			->with($section::class)
			->willReturn($section);

		$this->withRegisteredSections([
			new ServiceRegistration('testing', $section::class),
		]);

		$this->assertSame([$section], $this->manager->getSections());
	}

	public function testGetSectionsSkipsSectionThrowingDuringCollection(): void {
		$section = $this->createMock(ISystemReportSection::class);
		$section->method('getDetails')
			->willThrowException(new \RuntimeException('boom'));

		$this->container->method('get')
			->with($section::class)
			->willReturn($section);

		$this->withRegisteredSections([
			new ServiceRegistration('testing', $section::class),
		]);

		$this->logger->expects(self::once())
			->method('error');

		$this->assertSame([], $this->manager->getSections());
	}

	public function testGetSectionsSkipsUnresolvableClass(): void {
		$this->container->method('get')
			->with(\stdClass::class)
			->willThrowException($this->createStub(NotFoundExceptionInterface::class));

		$this->withRegisteredSections([
			new ServiceRegistration('testing', \stdClass::class),
		]);

		$this->logger->expects(self::once())
			->method('error');

		$this->assertSame([], $this->manager->getSections());
	}
}
