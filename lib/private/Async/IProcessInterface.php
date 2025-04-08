<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async;

use OC\Async\Enum\ProcessExecutionTime;

interface IProcessInterface {
	public function getToken(): string;
	public function blocker(): self;
	public function dataset(array $dataset): self;
	public function async(ProcessExecutionTime $time):  self;
}
