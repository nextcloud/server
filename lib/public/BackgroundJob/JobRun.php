<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\BackgroundJob;

use DateTimeImmutable;

/**
 * Job run
 *
 * Information about the execution of a single job
 *
 * @since 34.0.0
 */
final readonly class JobRun {
	/**
	 * Constructor
	 *
	 * @since 34.0.0
	 */
	public function __construct(
		/** Run ID (Snowflake ID) */
		public int|string $runId,
		/** Class name */
		public string $className,
		/** Server ID */
		public int $serverId,
		/** Process ID on server */
		public int $pid,
		/** Job start time */
		public DateTimeImmutable $startedAt,
		/** Job status (running, fail…) */
		public JobStatus $status,
		/** Job duration in milliseconds */
		public ?int $duration = null,
		/** Job memory usage peak in kilobytes (base 10) */
		public ?int $memoryPeak = null,
	) {
	}
}
