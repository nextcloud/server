<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Support\CrashReport;

/**
 * @since 15.0.0
 */
interface ICollectBreadcrumbs extends IReporter {
	/**
	 * Collect breadcrumbs for crash reports
	 *
	 * @param string $message
	 * @param string $category
	 * @param array $context
	 *
	 * @since 15.0.0
	 */
	public function collect(string $message, string $category, array $context = []);
}
