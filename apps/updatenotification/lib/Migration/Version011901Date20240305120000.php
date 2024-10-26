<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UpdateNotification\Migration;

use OCA\UpdateNotification\BackgroundJob\ResetToken;
use OCA\UpdateNotification\Notification\BackgroundJob;
use OCA\UpdateNotification\ResetTokenBackgroundJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Drop this with Nextcloud 30
 */
class Version011901Date20240305120000 extends SimpleMigrationStep {

	public function __construct(
		private IJobList $joblist,
	) {
	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		/**
		 * Remove and replace the reset-updater-token background job
		 * This class was renamed so it is now unknow but we still need to remove it
		 * @psalm-suppress UndefinedClass, InvalidArgument
		 */
		$hasOldResetToken = $this->joblist->has(ResetTokenBackgroundJob::class, null);
		$hasNewResetToken = $this->joblist->has(ResetToken::class, null);
		if ($hasOldResetToken) {
			/**
			 * @psalm-suppress UndefinedClass, InvalidArgument
			 */
			$this->joblist->remove(ResetTokenBackgroundJob::class);
			if (!$hasNewResetToken) {
				$this->joblist->add(ResetToken::class);
			}
		}

		/**
		 * Remove the "has updates" background job, the new one is automatically started from the info.xml
		 * @psalm-suppress UndefinedClass, InvalidArgument
		 */
		if ($this->joblist->has(BackgroundJob::class, null)) {
			/**
			 * @psalm-suppress UndefinedClass, InvalidArgument
			 */
			$this->joblist->remove(BackgroundJob::class);
		}
	}
}
