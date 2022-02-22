<?php

declare(strict_types=1);

/**
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
namespace OCA\DAV\BackgroundJob;

use OCA\DAV\CalDAV\RetentionService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class CalendarRetentionJob extends TimedJob {
	/** @var RetentionService */
	private $service;

	public function __construct(ITimeFactory $time,
								RetentionService $service) {
		parent::__construct($time);
		$this->service = $service;

		// Run four times a day
		$this->setInterval(6 * 60 * 60);
		$this->setTimeSensitivity(self::TIME_SENSITIVE);
	}

	protected function run($argument): void {
		$this->service->cleanUp();
	}
}
