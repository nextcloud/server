<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AdminAudit\BackgroundJobs;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use OCP\Log\RotationTrait;

class Rotate extends TimedJob {
	use RotationTrait;

	public function __construct(
		ITimeFactory $time,
		private IConfig $config,
	) {
		parent::__construct($time);

		$this->setInterval(60 * 60 * 3);
	}

	protected function run($argument): void {
		$default = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data') . '/audit.log';
		$this->filePath = $this->config->getAppValue('admin_audit', 'logfile', $default);

		if ($this->filePath === '') {
			// default log file, nothing to do
			return;
		}

		$this->maxSize = $this->config->getSystemValue('log_rotate_size', 100 * 1024 * 1024);

		if ($this->shouldRotateBySize()) {
			$this->rotate();
		}
	}
}
