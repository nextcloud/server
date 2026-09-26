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
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

final class SystemReportManagerTest extends TestCase {
	private SystemReportManager $manager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->manager = $this->createInstanceWithMocks(SystemReportManager::class);
	}

	/**
	 * @param ServiceRegistration[] $registrations
	 */
	private function withRegisteredSections(array $registrations): void {
		$context = $this->createMock(RegistrationContext::class);
		$context->expects(self::atLeastOnce())
			->method('getSystemReportSections')
			->willReturn($registrations);

		$this->mocks[Coordinator::class]->expects(self::atLeastOnce())
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

		$this->mocks[ContainerInterface::class]->expects(self::once())
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

		$this->mocks[ContainerInterface::class]->method('get')
			->with($section::class)
			->willReturn($section);

		$this->withRegisteredSections([
			new ServiceRegistration('testing', $section::class),
		]);

		$this->mocks[LoggerInterface::class]->expects(self::once())
			->method('error');

		$this->assertSame([], $this->manager->getSections());
	}

	public function testGetSectionsSkipsUnresolvableClass(): void {
		$this->mocks[ContainerInterface::class]->method('get')
			->with(\stdClass::class)
			->willThrowException($this->createStub(NotFoundExceptionInterface::class));

		$this->withRegisteredSections([
			new ServiceRegistration('testing', \stdClass::class),
		]);

		$this->mocks[LoggerInterface::class]->expects(self::once())
			->method('error');

		$this->assertSame([], $this->manager->getSections());
	}
}
