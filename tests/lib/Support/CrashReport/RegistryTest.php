<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Support\CrashReport;

use Exception;
use OC\Support\CrashReport\Registry;
use OCP\AppFramework\QueryException;
use OCP\IServerContainer;
use OCP\Support\CrashReport\ICollectBreadcrumbs;
use OCP\Support\CrashReport\IMessageReporter;
use OCP\Support\CrashReport\IReporter;
use Test\TestCase;

class RegistryTest extends TestCase {
	/** @var IServerContainer|\PHPUnit\Framework\MockObject\MockObject */
	private $serverContainer;

	/** @var Registry */
	private $registry;

	protected function setUp(): void {
		parent::setUp();

		$this->serverContainer = $this->createMock(IServerContainer::class);

		$this->registry = new Registry(
			$this->serverContainer
		);
	}

	/**
	 * Doesn't assert anything, just checks whether anything "explodes"
	 */
	public function testDelegateToNone(): void {
		$exception = new Exception('test');

		$this->registry->delegateReport($exception);
		$this->addToAssertionCount(1);
	}

	public function testRegisterLazyCantLoad(): void {
		$reporterClass = '\OCA\MyApp\Reporter';
		$reporter = $this->createMock(IReporter::class);
		$this->serverContainer->expects($this->once())
			->method('query')
			->with($reporterClass)
			->willReturn($reporter);
		$reporter->expects($this->once())
			->method('report');
		$exception = new Exception('test');

		$this->registry->registerLazy($reporterClass);
		$this->registry->delegateReport($exception);
	}

	public function testRegisterLazy(): void {
		$reporterClass = '\OCA\MyApp\Reporter';
		$this->serverContainer->expects($this->once())
			->method('query')
			->with($reporterClass)
			->willThrowException(new QueryException());
		$exception = new Exception('test');

		$this->registry->registerLazy($reporterClass);
		$this->registry->delegateReport($exception);
	}

	public function testDelegateBreadcrumbCollection(): void {
		$reporter1 = $this->createMock(IReporter::class);
		$reporter2 = $this->createMock(ICollectBreadcrumbs::class);
		$message = 'hello';
		$category = 'log';
		$reporter2->expects($this->once())
			->method('collect')
			->with($message, $category);

		$this->registry->register($reporter1);
		$this->registry->register($reporter2);
		$this->registry->delegateBreadcrumb($message, $category);
	}

	public function testDelegateToAll(): void {
		$reporter1 = $this->createMock(IReporter::class);
		$reporter2 = $this->createMock(IReporter::class);
		$exception = new Exception('test');
		$reporter1->expects($this->once())
			->method('report')
			->with($exception);
		$reporter2->expects($this->once())
			->method('report')
			->with($exception);

		$this->registry->register($reporter1);
		$this->registry->register($reporter2);
		$this->registry->delegateReport($exception);
	}

	public function testDelegateMessage(): void {
		$reporter1 = $this->createMock(IReporter::class);
		$reporter2 = $this->createMock(IMessageReporter::class);
		$message = 'hello';
		$reporter2->expects($this->once())
			->method('reportMessage')
			->with($message, []);

		$this->registry->register($reporter1);
		$this->registry->register($reporter2);
		$this->registry->delegateMessage($message);
	}
}
