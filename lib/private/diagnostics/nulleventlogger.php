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

/**
 * Dummy event logger that doesn't actually log anything
 */
class NullEventLogger implements IEventLogger {
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

	public function log($id, $description, $start, $end) {
	}

	/**
	 * @return \OCP\Diagnostics\IEvent[]
	 */
	public function getEvents() {
		return array();
	}
}
