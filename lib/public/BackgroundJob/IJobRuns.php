<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\BackgroundJob;

/**
 * List executed jobs
 *
 * Keep track of background jobs: start time, resource used, exit status…
 *
 * @since 34.0.0
 */
interface IJobRuns {
	/**
	 * List of currently running jobs
	 *
	 * @since 34.0.0
	 */
	public function runningJobs(int $limit = 200): \Generator;

	/**
	 * List of completed jobs
	 *
	 * @param list<JobStatus> $statuses
	 * @param list<class-string<IJob>> $classes
	 * @since 34.0.0
	 */
	public function completedJobs(array $statuses = [], array $classes = [], int $limit = 200): \Generator;
}
