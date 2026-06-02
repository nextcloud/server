<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\BackgroundJob;

/**
 * Background job statuses
 *
 * @since 34.0.0
 */
enum JobStatus: int {
	/**
	 * Background job is still running
	 *
	 * @since 34.0.0
	 */
	case RUNNING = 0;

	/**
	 * Background job completed sucessfully
	 *
	 * @since 34.0.0
	 */
	case SUCCEEDED = 1;

	/**
	 * Background job failed
	 *
	 * @since 34.0.0
	 */
	case FAILED = 2;

	/**
	 * Background job crashed the PHP process
	 *
	 * @since 34.0.0
	 */
	case CRASHED = 3;
}
