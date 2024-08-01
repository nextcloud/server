<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\BackgroundJobs;

use OCA\WorkflowEngine\AppInfo\Application;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\Log\RotationTrait;

class Rotate extends TimedJob {
	use RotationTrait;

	public function __construct(ITimeFactory $time) {
		parent::__construct($time);
		$this->setInterval(60 * 60 * 3);
	}

	protected function run($argument) {
		$config = \OC::$server->getConfig();
		$default = $config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data') . '/flow.log';
		$this->filePath = trim((string)$config->getAppValue(Application::APP_ID, 'logfile', $default));

		if ($this->filePath === '') {
			// disabled, nothing to do
			return;
		}

		$this->maxSize = $config->getSystemValue('log_rotate_size', 100 * 1024 * 1024);

		if ($this->shouldRotateBySize()) {
			$this->rotate();
		}
	}
}
