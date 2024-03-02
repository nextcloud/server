<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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
		if ($this->config->getSystemValueBool('config_is_read_only') !== false) {
			return;
		}

		$secretCreated = $this->appConfig->getValueInt('core', 'updater.secret.created', $this->time->getTime());
		// Delete old tokens after 2 days
		if ($secretCreated >= 172800) {
			$this->config->deleteSystemValue('updater.secret');
		}
	}
}
