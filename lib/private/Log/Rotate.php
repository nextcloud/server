<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Log;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use OCP\Log\RotationTrait;
use Psr\Log\LoggerInterface;

/**
 * Rotates the current logfile to a new name.
 *
 * The log file is checked against the configured maximum size at each job
 * interval, independently of log writing. Only the most recent rotated file
 * is kept.
 *
 * This rotation is only intended to be used for small/simple deployments. Use
 * `logrotate` (or a similar tool) for more elaborate or robust rotation
 * management.
 */
class Rotate extends TimedJob {
	use RotationTrait;

	public function __construct(
		ITimeFactory $time,
		private IConfig $config,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);

		$this->setInterval(self::DEFAULT_ROTATION_INTERVAL);
	}

	#[\Override]
	public function run($argument): void {
		$defaultFilePath = $this->config->getSystemValueString('datadirectory', \OC::$SERVERROOT . '/data') . '/nextcloud.log';

		$this->filePath = $this->config->getSystemValueString('logfile', $defaultFilePath);
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
