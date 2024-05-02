<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
namespace OCA\UserStatus\BackgroundJob;

use OCA\UserStatus\Db\UserStatusMapper;
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

/**
 * Class ClearOldStatusesBackgroundJob
 *
 * @package OCA\UserStatus\BackgroundJob
 */
class ClearOldStatusesBackgroundJob extends TimedJob {

	/** @var UserStatusMapper */
	private $mapper;

	/**
	 * ClearOldStatusesBackgroundJob constructor.
	 *
	 * @param ITimeFactory $time
	 * @param UserStatusMapper $mapper
	 */
	public function __construct(ITimeFactory $time,
		UserStatusMapper $mapper) {
		parent::__construct($time);
		$this->mapper = $mapper;

		// Run every time the cron is run
		$this->setInterval(60);
	}

	/**
	 * @inheritDoc
	 */
	protected function run($argument) {
		$now = $this->time->getTime();

		$this->mapper->clearOlderThanClearAt($now);
		$this->mapper->clearStatusesOlderThan($now - StatusService::INVALIDATE_STATUS_THRESHOLD, $now);
	}
}
