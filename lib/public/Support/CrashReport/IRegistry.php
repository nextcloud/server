<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
namespace OCP\Support\CrashReport;

use Exception;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use Throwable;

/**
 * @since 13.0.0
 * @deprecated used internally only
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
	 * @deprecated used internally only
	 * @since 15.0.0
	 */
	public function delegateBreadcrumb(string $message, string $category, array $context = []): void;

	/**
	 * Delegate crash reporting to all registered reporters
	 *
	 * @param Exception|Throwable $exception
	 * @param array $context
	 *
	 * @deprecated used internally only
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
	 * @deprecated used internally only
	 * @since 17.0.0
	 */
	public function delegateMessage(string $message, array $context = []): void;

	/**
	 * Check if any reporter has been registered to delegate to
	 *
	 * @return bool
	 * @deprecated use internally only
	 * @since 26.0.0
	 */
	public function hasReporters(): bool;
}
