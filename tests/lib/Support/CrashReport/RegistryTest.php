<?php

declare(strict_types=1);

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
