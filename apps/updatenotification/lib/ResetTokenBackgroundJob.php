<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
namespace OCA\UpdateNotification;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;

/**
 * Class ResetTokenBackgroundJob deletes any configured token all 24 hours for
 *
 *
 * @package OCA\UpdateNotification
 */
class ResetTokenBackgroundJob extends TimedJob {
	/** @var IConfig */
	private $config;
	/** @var ITimeFactory */
	private $timeFactory;

	/**
	 * @param IConfig $config
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct(IConfig $config,
		ITimeFactory $timeFactory) {
		parent::__construct($timeFactory);
		// Run all 10 minutes
		parent::setInterval(60 * 10);
		$this->config = $config;
		$this->timeFactory = $timeFactory;
	}

	/**
	 * @param $argument
	 */
	protected function run($argument) {
		// Delete old tokens after 2 days
		if ($this->config->getSystemValueBool('config_is_read_only') === false && $this->timeFactory->getTime() - (int) $this->config->getAppValue('core', 'updater.secret.created', (string) $this->timeFactory->getTime()) >= 172800) {
			$this->config->deleteSystemValue('updater.secret');
		}
	}
}
