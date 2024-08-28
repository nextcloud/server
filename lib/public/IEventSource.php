<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP;

/**
 * wrapper for server side events (http://en.wikipedia.org/wiki/Server-sent_events)
 * includes a fallback for older browsers and IE
 *
 * use server side events with caution, to many open requests can hang the server
 *
 * The event source will initialize the connection to the client when the first data is sent
 * @since 8.0.0
 */
interface IEventSource {
	/**
	 * send a message to the client
	 *
	 * @param string $type One of success, notice, error, failure and done. Used in core/js/update.js
	 * @param mixed $data
	 * @return void
	 *
	 * if only one parameter is given, a typeless message will be send with that parameter as data
	 * @since 8.0.0
	 */
	public function send($type, $data = null);

	/**
	 * close the connection of the event source
	 * @return void
	 * @since 8.0.0
	 */
	public function close();
}
