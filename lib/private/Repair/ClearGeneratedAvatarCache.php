<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair;

use OC\Avatar\AvatarManager;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
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
		return 'Clear every generated avatar';
	}

	/**
	 * Check if this repair step should run
	 */
	private function shouldRun(): bool {
		$versionFromBeforeUpdate = $this->config->getSystemValueString('version', '0.0.0.0');

		// This job only runs if the server was on a version lower than or equal to 27.0.0 before the upgrade.
		// To clear the avatar cache again, bump the version to the currently released version (and change the operator to <= if it's not the master branch) and wait for the next release.
		return version_compare($versionFromBeforeUpdate, '27.0.0', '<');
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
