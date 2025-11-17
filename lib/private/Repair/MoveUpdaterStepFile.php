<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair;

use OCP\Files;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class MoveUpdaterStepFile implements IRepairStep {
	/**
	 * @param IConfig $config
	 */
	public function __construct(
		protected $config,
	) {
	}

	public function getName() {
		return 'Move .step file of updater to backup location';
	}

	public function run(IOutput $output) {
		$updateDir = $this->config->getSystemValue('updatedirectory', null) ?? $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data');
		$instanceId = $this->config->getSystemValueString('instanceid');

		if (empty($instanceId)) {
			return;
		}

		$updaterFolderPath = $updateDir . '/updater-' . $instanceId;
		$stepFile = $updaterFolderPath . '/.step';
		if (file_exists($stepFile)) {
			$output->info('.step file exists');

			$previousStepFile = $updaterFolderPath . '/.step-previous-update';

			// cleanup
			if (file_exists($previousStepFile)) {
				if (Files::rmdirr($previousStepFile)) {
					$output->info('.step-previous-update removed');
				} else {
					$output->info('.step-previous-update can\'t be removed - abort move of .step file');
					return;
				}
			}

			// move step file
			if (rename($stepFile, $previousStepFile)) {
				$output->info('.step file moved to .step-previous-update');
			} else {
				$output->warning('.step file can\'t be moved');
			}
		}
	}
}
