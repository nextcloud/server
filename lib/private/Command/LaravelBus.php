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

use Illuminate\Queue\Capsule\Manager as Queue;
use OCP\Command\IBus;

class LaravelBus implements IBus {
	use AsyncBusTrait;

	/** @var  Queue */
	private $queue;

	/**
	 * @param Queue $queue
	 */
	function __construct($queue) {
		$this->queue = $queue;
	}

	/**
	 * Schedule a command to be fired
	 *
	 * @param \OCP\Command\ICommand | callable $command
	 */
	public function push($command) {
		if ($this->canRunAsync($command)) {
			$this->queue->push($command);
		} else {
			$this->runCommand($command);
		}
	}

	/**
	 * @return Queue
	 */
	public function getQueue() {
		return $this->queue;
	}
}
