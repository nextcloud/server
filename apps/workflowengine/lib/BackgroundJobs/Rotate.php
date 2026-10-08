<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WorkflowEngine\BackgroundJobs;

use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use OCP\Log\RotationTrait;
use Psr\Log\LoggerInterface;

class Rotate extends TimedJob {
	use RotationTrait;

	public function __construct(
		ITimeFactory $time,
		private IAppConfig $appConfig,
		private IConfig $config,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
		$this->setInterval(self::DEFAULT_ROTATION_INTERVAL);
	}

	#[\Override]
	protected function run($argument): void {
		$defaultFilePath = $this->config->getSystemValueString('datadirectory', \OC::$SERVERROOT . '/data') . '/flow.log';
		$this->filePath = $this->appConfig->getAppValueString('logfile', $defaultFilePath);
		if ($this->filePath === '') {
			return;
		}

		$this->maxSize = $this->config->getSystemValueInt('log_rotate_size', self::DEFAULT_MAX_SIZE);
		if ($this->shouldRotateBySize()) {
			$rotatedFile = $this->rotate();
			$this->logger->info(
				'Log file "{filePath}" reached the configured rotation size of {maxSize} bytes and was moved to "{rotatedFile}"',
				[
					'app' => self::class,
					'filePath' => $this->filePath,
					'maxSize' => $this->maxSize,
					'rotatedFile' => $rotatedFile,
				],
			);
		}
	}
}
