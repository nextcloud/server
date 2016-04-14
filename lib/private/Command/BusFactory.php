<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Command;

use OC\Connector\Laravel\RedisQueueFactory;
use OC\RedisFactory;
use OC\SystemConfig;
use OCP\BackgroundJob\IJobList;
use OCP\Command\IBus;

class BusFactory {
	/** @var  SystemConfig */
	private $systemConfig;

	/** @var  IJobList */
	private $jobList;

	/** @var  RedisFactory */
	private $redisFactory;

	/** @var  IBus */
	private $bus;

	/**
	 * BusFactory constructor.
	 *
	 * @param SystemConfig $systemConfig
	 * @param IJobList $jobList
	 * @param RedisFactory $redisFactory
	 */
	public function __construct(SystemConfig $systemConfig, IJobList $jobList, RedisFactory $redisFactory) {
		$this->systemConfig = $systemConfig;
		$this->jobList = $jobList;
		$this->redisFactory = $redisFactory;
	}

	private function create() {
		if ($this->systemConfig->getValue('commandbus', 'cron') === 'redis') {
			$redis = $this->redisFactory->getInstance();
			$queue = (new RedisQueueFactory())->getQueue($redis);
			$this->bus = new LaravelBus($queue);
		} else {
			$this->bus = new AsyncBus($this->jobList);
		}
	}

	public function getAsyncBus() {
		if (!$this->bus instanceof IBus) {
			$this->create();
		}
		return $this->bus;
	}
}
