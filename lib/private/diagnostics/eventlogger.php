<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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

	/**
	 * @return \OCP\Diagnostics\IEvent[]
	 */
	public function getEvents() {
		return $this->events;
	}
}
