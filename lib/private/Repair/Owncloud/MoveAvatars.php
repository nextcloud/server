<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\Owncloud;

use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class MoveAvatars implements IRepairStep {
	/** @var IJobList */
	private $jobList;

	/** @var IConfig */
	private $config;

	/**
	 * MoveAvatars constructor.
	 *
	 * @param IJobList $jobList
	 * @param IConfig $config
	 */
	public function __construct(IJobList $jobList,
		IConfig $config) {
		$this->jobList = $jobList;
		$this->config = $config;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'Add move avatar background job';
	}

	public function run(IOutput $output) {
		// only run once
		if ($this->config->getAppValue('core', 'moveavatarsdone') === 'yes') {
			$output->info('Repair step already executed');
			return;
		}
		if (!$this->config->getSystemValueBool('enable_avatars', true)) {
			$output->info('Avatars are disabled');
		} else {
			$output->info('Add background job');
			$this->jobList->add(MoveAvatarsBackgroundJob::class);
			// if all were done, no need to redo the repair during next upgrade
			$this->config->setAppValue('core', 'moveavatarsdone', 'yes');
		}
	}
}
