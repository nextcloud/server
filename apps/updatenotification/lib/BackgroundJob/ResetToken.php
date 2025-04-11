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
	) {
		parent::__construct($time);
		// Run all 10 minutes
		parent::setInterval(60 * 10);
	}

	/**
	 * @param $argument
	 */
	protected function run($argument) {
		if ($this->config->getSystemValueBool('config_is_read_only')) {
			return;
		}

		$secretCreated = $this->appConfig->getValueInt('core', 'updater.secret.created', $this->time->getTime());
		// Delete old tokens after 2 days
		if ($secretCreated >= 172800) {
			$this->config->deleteSystemValue('updater.secret');
		}
	}
}
