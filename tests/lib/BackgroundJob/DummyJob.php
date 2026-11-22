<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\BackgroundJob;

use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\IJobList;

/**
 * Dummy Job fo tests only
 */
class DummyJob implements IJob {
	public function start(IJobList $jobList): void {
	}

	public function setId(string $id): void {
	}

	public function setLastRun(int $lastRun): void {
	}

	public function setArgument(mixed $argument): void {
	}

	public function getId(): string {
	}

	public function getLastRun(): int {
	}

	public function getArgument(): mixed {
	}
}
