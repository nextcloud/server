<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Support\CrashReport;

use Exception;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use Throwable;

/**
 * @since 13.0.0
 * @deprecated 20.0.0 used internally only
 */
interface IRegistry {
	/**
	 * Register a reporter instance
	 *
	 * @param IReporter $reporter
	 *
	 * @since 13.0.0
	 * @deprecated 20.0.0 use IRegistrationContext::registerCrashReporter
	 * @see IRegistrationContext::registerCrashReporter()
	 */
	public function register(IReporter $reporter): void;

	/**
	 * Delegate breadcrumb collection to all registered reporters
	 *
	 * @param string $message
	 * @param string $category
	 * @param array $context
	 *
	 * @deprecated 20.0.0 used internally only
	 * @since 15.0.0
	 */
	public function delegateBreadcrumb(string $message, string $category, array $context = []): void;

	/**
	 * Delegate crash reporting to all registered reporters
	 *
	 * @param Exception|Throwable $exception
	 * @param array $context
	 *
	 * @deprecated 20.0.0 used internally only
	 * @since 13.0.0
	 */
	public function delegateReport($exception, array $context = []);

	/**
	 * Delegate a message to all reporters that implement IMessageReporter
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 *
	 * @deprecated 20.0.0 used internally only
	 * @since 17.0.0
	 */
	public function delegateMessage(string $message, array $context = []): void;

	/**
	 * Check if any reporter has been registered to delegate to
	 *
	 * @return bool
	 * @deprecated 20.0.0 use internally only
	 * @since 26.0.0
	 */
	public function hasReporters(): bool;
}
