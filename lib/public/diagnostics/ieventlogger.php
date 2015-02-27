<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Diagnostics;

interface IEventLogger {
	/**
	 * Mark the start of an event
	 *
	 * @param string $id
	 * @param string $description
	 */
	public function start($id, $description);

	/**
	 * Mark the end of an event
	 *
	 * @param string $id
	 */
	public function end($id);

	/**
	 * @param string $id
	 * @param string $description
	 * @param float $start
	 * @param float $end
	 */
	public function log($id, $description, $start, $end);

	/**
	 * @return \OCP\Diagnostics\IEvent[]
	 */
	public function getEvents();
}
