<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Diagnostics;

/**
 * Interface IEventLogger
 *
 * @since 8.0.0
 */
interface IEventLogger {
	/**
	 * Mark the start of an event setting its ID $id and providing event description $description.
	 *
	 * @param string $id
	 * @param string $description
	 * @since 8.0.0
	 */
	public function start($id, $description);

	/**
	 * Mark the end of an event with specific ID $id, marked by start() method.
	 * Ending event should store \OCP\Diagnostics\IEvent to
	 * be returned with getEvents() method.
	 *
	 * @param string $id
	 * @since 8.0.0
	 */
	public function end($id);

	/**
	 * Mark the start and the end of an event with specific ID $id and description $description,
	 * explicitly marking start and end of the event, represented by $start and $end timestamps.
	 * Logging event should store \OCP\Diagnostics\IEvent to
	 * be returned with getEvents() method.
	 *
	 * @param string $id
	 * @param string $description
	 * @param float $start
	 * @param float $end
	 * @since 8.0.0
	 */
	public function log($id, $description, $start, $end);

	/**
	 * This method should return all \OCP\Diagnostics\IEvent objects stored using
	 * start()/end() or log() methods
	 *
	 * @return \OCP\Diagnostics\IEvent[]
	 * @since 8.0.0
	 */
	public function getEvents();

	/**
	 * Activate the module for the duration of the request. Deactivated module
	 * does not create and store \OCP\Diagnostics\IEvent objects.
	 * Only activated module should create and store objects to be
	 * returned with getEvents() call.
	 *
	 * @since 12.0.0
	 */
	public function activate();
}
