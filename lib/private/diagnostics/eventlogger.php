<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
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

namespace OC\Diagnostics;

use OCP\Diagnostics\IEventLogger;

class EventLogger implements IEventLogger {
	/**
	 * @var \OC\Diagnostics\Event[]
	 */
	private $events = array();

	public function start($id, $description) {
		$this->events[$id] = new Event($id, $description, microtime(true));
	}

	public function end($id) {
		if (isset($this->events[$id])) {
			$timing = $this->events[$id];
			$timing->end(microtime(true));
		}
	}

	public function log($id, $description, $start, $end) {
		$this->events[$id] = new Event($id, $description, $start);
		$this->events[$id]->end($end);
	}

	/**
	 * @return \OCP\Diagnostics\IEvent[]
	 */
	public function getEvents() {
		return $this->events;
	}
}
