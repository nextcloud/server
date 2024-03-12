<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\UpdateNotification\Migration;

use OCA\UpdateNotification\BackgroundJob\ResetToken;
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
		$hasOldResetToken = $this->joblist->has(\OCA\UpdateNotification\ResetTokenBackgroundJob::class, null);
		$hasNewResetToken = $this->joblist->has(ResetToken::class, null);
		if ($hasOldResetToken) {
			/**
			 * @psalm-suppress UndefinedClass, InvalidArgument
			 */
			$this->joblist->remove(\OCA\UpdateNotification\ResetTokenBackgroundJob::class);
			if (!$hasNewResetToken) {
				$this->joblist->add(ResetToken::class);
			}
		}

		/**
		 * Remove the "has updates" background job, the new one is automatically started from the info.xml
		 * @psalm-suppress UndefinedClass, InvalidArgument
		 */
		if ($this->joblist->has(\OCA\UpdateNotification\Notification\BackgroundJob::class, null)) {
			/**
			 * @psalm-suppress UndefinedClass, InvalidArgument
			 */
			$this->joblist->remove(\OCA\UpdateNotification\Notification\BackgroundJob::class);
		}
	}
}
