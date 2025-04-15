<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async\Wrappers;

use OC\Async\Enum\ProcessActivity;
use OC\Async\AProcessWrapper;
use OC\Async\Model\Process;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

class DummyProcessWrapper extends AProcessWrapper {
	public function session(array $metadata): void {
	}
	public function init(Process $process): void {
	}

	public function activity(ProcessActivity $activity, string $line = ''): void {
	}

	public function end(string $line = ''): void {
	}
}
