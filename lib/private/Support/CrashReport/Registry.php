<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Support\CrashReport;

use Exception;
use OCP\AppFramework\QueryException;
use OCP\Server;
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

	/**
	 * Register a reporter instance
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
				$reporter = Server::get($class);
			} catch (QueryException $e) {
				/*
				 * There is a circular dependency between the logger and the registry, so
				 * we can not inject it. Thus the static call.
				 */
				Server::get(LoggerInterface::class)->critical('Could not load lazy crash reporter: ' . $e->getMessage(), [
					'exception' => $e,
				]);
				return;
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
				Server::get(LoggerInterface::class)->critical('Could not register lazy crash reporter: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		}
	}

	public function hasReporters(): bool {
		return !empty($this->lazyReporters) || !empty($this->reporters);
	}
}
