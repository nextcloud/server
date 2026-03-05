<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Migration;

use OCA\Theming\Jobs\MigrateBackgroundImages;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class InitBackgroundImagesMigration implements IRepairStep {

	public function __construct(
		private IJobList $jobList,
	) {
	}

	public function getName() {
		return 'Initialize migration of background images from dashboard to theming app';
	}

	public function run(IOutput $output) {
		$this->jobList->add(MigrateBackgroundImages::class, ['stage' => MigrateBackgroundImages::STAGE_PREPARE]);
	}
}
