<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
