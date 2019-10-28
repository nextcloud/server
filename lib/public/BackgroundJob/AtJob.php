<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\ILogger;

/**
 * @since 18.0.0
 *
 * Base class for a background job that should run once a day as close to the set
 * time as possible (later but as close to is as possible).
 *
 * So lets say it is set at 08:00 but the first cron job runs at 08:06, then it
 * will try to run the job
 */
abstract class AtJob extends Job {
	/** @var int */
	private $hour;
	/** @var int */
	private $minute;

	/**
	 * AtJob constructor.
	 *
	 * @param ITimeFactory $time
	 * @param int $hour The hour to run this job
	 * @param int $minute The minute to run this job
	 *
	 * @since 18.0.0
	 */
	public function __construct(ITimeFactory $time, int $hour, int $minute) {
		parent::__construct($time);
		$this->hour = $hour;
		$this->minute = $minute;
	}

	private function verifyTime() {
		if ($this->hour < 0 || $this->hour > 23
			|| $this->minute < 0 || $this->minute > 59) {
			throw new \RuntimeException('Invalid minute specified: ' . $this->hour . ':' . $this->minute);
		}
	}

	/**
	 * @since 18.0.0
	 */
	public function execute($jobList, ILogger $logger = null) {
		$this->verifyTime();

		$last = new \DateTime();
		$last->setTimestamp($this->lastRun);
		$last->setTime($this->hour,$this->minute, 0, 0);

		$now = $this->time->getDateTime();
		$now->setTime($this->hour,$this->minute,0,0);

		$diff = $now->diff($last, true);
		if ($diff->days >= 1) {
			parent::execute($jobList, $logger);
		}
	}
}
