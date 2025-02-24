<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\NC29;

use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class SanitizeAccountProperties implements IRepairStep {

	public function __construct(
		private IJobList $jobList,
	) {
	}

	public function getName(): string {
		return 'Validate account properties and store phone numbers in a known format for search';
	}

	public function run(IOutput $output): void {
		$this->jobList->add(SanitizeAccountPropertiesJob::class, null);
		$output->info('Queued background to validate account properties.');
	}
}
