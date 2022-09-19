<?php
/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Michael Weimann <mail@michael-weimann.eu>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Repair;

use OC\Avatar\AvatarManager;
use OCP\IConfig;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class ClearGeneratedAvatarCache implements IRepairStep {
	protected AvatarManager $avatarManager;
	private IConfig $config;
	private IJobList $jobList;

	public function __construct(IConfig $config, AvatarManager $avatarManager, IJobList $jobList) {
		$this->config = $config;
		$this->avatarManager = $avatarManager;
		$this->jobList = $jobList;
	}

	public function getName(): string {
		return 'Clear every generated avatar on major updates';
	}

	/**
	 * Check if this repair step should run
	 */
	private function shouldRun(): bool {
		$versionFromBeforeUpdate = $this->config->getSystemValue('version', '0.0.0.0');

		// was added to 25.0.0.10
		return version_compare($versionFromBeforeUpdate, '25.0.0.10', '<=');
	}

	public function run(IOutput $output): void {
		if ($this->shouldRun()) {
			try {
				$this->jobList->add(ClearGeneratedAvatarCacheJob::class, []);
				$output->info('Avatar cache clearing job added');
			} catch (\Exception $e) {
				$output->warning('Unable to clear the avatar cache');
			}
		}
	}
}
