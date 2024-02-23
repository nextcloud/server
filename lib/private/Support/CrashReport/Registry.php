<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Morris Jobke <hey@morrisjobke.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Support\CrashReport;

use Exception;
use OCP\AppFramework\QueryException;
use OCP\IServerContainer;
use OCP\Support\CrashReport\ICollectBreadcrumbs;
use OCP\Support\CrashReport\IMessageReporter;
use OCP\Support\CrashReport\IRegistry;
use OCP\Support\CrashReport\IReporter;
use Psr\Log\LoggerInterface;
use Throwable;
use function array_shift;

class Registry implements IRegistry {
	/** @var string[] */
	private $lazyReporters = [];

	/** @var IReporter[] */
	private $reporters = [];

	/** @var IServerContainer */
	private $serverContainer;

	public function __construct(IServerContainer $serverContainer) {
		$this->serverContainer = $serverContainer;
	}

	/**
	 * Register a reporter instance
	 *
	 * @param IReporter $reporter
	 */
	public function register(IReporter $reporter): void {
		$this->reporters[] = $reporter;
	}

	public function registerLazy(string $class): void {
		$this->lazyReporters[] = $class;
	}

	/**
	 * Delegate breadcrumb collection to all registered reporters
	 *
	 * @param string $message
	 * @param string $category
	 * @param array $context
	 *
	 * @since 15.0.0
	 */
	public function delegateBreadcrumb(string $message, string $category, array $context = []): void {
		$this->loadLazyProviders();

		foreach ($this->reporters as $reporter) {
			if ($reporter instanceof ICollectBreadcrumbs) {
				$reporter->collect($message, $category, $context);
			}
		}
	}

	/**
	 * Delegate crash reporting to all registered reporters
	 *
	 * @param Exception|Throwable $exception
	 * @param array $context
	 */
	public function delegateReport($exception, array $context = []): void {
		$this->loadLazyProviders();

		foreach ($this->reporters as $reporter) {
			$reporter->report($exception, $context);
		}
	}

	/**
	 * Delegate a message to all reporters that implement IMessageReporter
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function delegateMessage(string $message, array $context = []): void {
		$this->loadLazyProviders();

		foreach ($this->reporters as $reporter) {
			if ($reporter instanceof IMessageReporter) {
				$reporter->reportMessage($message, $context);
			}
		}
	}

	private function loadLazyProviders(): void {
		while (($class = array_shift($this->lazyReporters)) !== null) {
			try {
				/** @var IReporter $reporter */
				$reporter = $this->serverContainer->query($class);
			} catch (QueryException $e) {
				/*
				 * There is a circular dependency between the logger and the registry, so
				 * we can not inject it. Thus the static call.
				 */
				\OC::$server->get(LoggerInterface::class)->critical('Could not load lazy crash reporter: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
			/**
			 * Try to register the loaded reporter. Theoretically it could be of a wrong
			 * type, so we might get a TypeError here that we should catch.
			 */
			try {
				$this->register($reporter);
			} catch (Throwable $e) {
				/*
				 * There is a circular dependency between the logger and the registry, so
				 * we can not inject it. Thus the static call.
				 */
				\OC::$server->get(LoggerInterface::class)->critical('Could not register lazy crash reporter: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		}
	}

	public function hasReporters(): bool {
		return !empty($this->lazyReporters) || !empty($this->reporters);
	}
}
