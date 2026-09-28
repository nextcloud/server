<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Support\CrashReport;

use Exception;
use Throwable;

/**
 * @since 13.0.0
 */
interface IReporter {
	/**
	 * Report an (unhandled) exception
	 *
	 * @since 13.0.0
	 * @param Exception|Throwable $exception
	 * @param array $context
	 */
	public function report($exception, array $context = []);
}
