<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\UpdateNotification\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IAppConfig;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Deletes the updater secret after if it is older than 48h
 */
class ResetToken extends TimedJob {

	/**
	 * @param IConfig $config
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct(
		ITimeFactory $time,
		private IConfig $config,
		private IAppConfig $appConfig,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
		// Run once an hour
		parent::setInterval(60 * 60);
	}

	/**
	 * @param $argument
	 */
	protected function run($argument) {
		if ($this->config->getSystemValueBool('config_is_read_only')) {
			$this->logger->debug('Skipping `updater.secret` reset since config_is_read_only is set', ['app' => 'updatenotification']);
			return;
		}

		$secretCreated = $this->appConfig->getValueInt('core', 'updater.secret.created', 0);

		if ($secretCreated === 0) {
			if ($this->config->getSystemValueString('updater.secret') !== '') {
				$this->logger->error('Cleared old `updater.secret` with unknown creation date', ['app' => 'updatenotification']);
				$this->config->deleteSystemValue('updater.secret');
			}
			$this->logger->debug('Skipping `updater.secret` reset since there is none', ['app' => 'updatenotification']);
			return;
		}

		// Delete old tokens after 2 days and also tokens without any created date
		$secretCreatedDiff = $this->time->getTime() - $secretCreated;
		if ($secretCreatedDiff >= 172800) {
			$this->config->deleteSystemValue('updater.secret');
			$this->appConfig->deleteKey('core', 'updater.secret.created');
			$this->logger->warning('Cleared old `updater.secret` that was created ' . $secretCreatedDiff . ' seconds ago', ['app' => 'updatenotification']);
		} else {
			$this->logger->debug('Keeping existing `updater.secret` that was created ' . $secretCreatedDiff . ' seconds ago', ['app' => 'updatenotification']);
		}
	}
}
