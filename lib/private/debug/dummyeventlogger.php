<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Debug;

use OCP\Debug\IEventLogger;

/**
 * Dummy event logger that doesn't actually log anything
 */
class DummyEventLogger implements IEventLogger {
	/**
	 * Mark the start of an event
	 *
	 * @param $id
	 * @param $description
	 */
	public function start($id, $description) {
	}

	/**
	 * Mark the end of an event
	 *
	 * @param $id
	 */
	public function end($id) {
	}

	/**
	 * @return \OCP\Debug\IEvent[]
	 */
	public function getEvents(){
		return array();
	}
}
