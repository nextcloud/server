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
use Psr\Log\LoggerInterface;
use Test\TestCase;

class SystemReportManagerTest extends TestCase {
	private Coordinator&MockObject $coordinator;
	private LoggerInterface&MockObject $logger;
	private SystemReportManager $manager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->coordinator = $this->createMock(Coordinator::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->manager = new SystemReportManager(
			$this->coordinator,
			$this->logger,
		);
	}

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

		\OC::$server->registerService('OCA\\Testing\\SystemReport\\FakeSection', fn () => $section, false);

		$this->withRegisteredSections([
			new ServiceRegistration('testing', 'OCA\\Testing\\SystemReport\\FakeSection'),
		]);

		$this->assertSame([$section], $this->manager->getSections());
	}

	public function testGetSectionsSkipsSectionThrowingDuringCollection(): void {
		$section = $this->createMock(ISystemReportSection::class);
		$section->method('getDetails')
			->willThrowException(new \RuntimeException('boom'));

		\OC::$server->registerService('OCA\\Testing\\SystemReport\\ThrowingSection', fn () => $section, false);

		$this->withRegisteredSections([
			new ServiceRegistration('testing', 'OCA\\Testing\\SystemReport\\ThrowingSection'),
		]);

		$this->logger->expects(self::once())
			->method('error');

		$this->assertSame([], $this->manager->getSections());
	}

	public function testGetSectionsSkipsUnresolvableClass(): void {
		$this->withRegisteredSections([
			new ServiceRegistration('testing', 'OCA\\Testing\\SystemReport\\DoesNotExist'),
		]);

		$this->logger->expects(self::once())
			->method('error');

		$this->assertSame([], $this->manager->getSections());
	}
}
