<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP;

/**
 * wrapper for server side events (http://en.wikipedia.org/wiki/Server-sent_events)
 * includes a fallback for older browsers and IE
 *
 * use server side events with caution, to many open requests can hang the server
 *
 * The event source will initialize the connection to the client when the first data is sent
 */
interface IEventSource {
	/**
	 * send a message to the client
	 *
	 * @param string $type
	 * @param mixed $data
	 *
	 * if only one parameter is given, a typeless message will be send with that parameter as data
	 */
	public function send($type, $data = null);

	/**
	 * close the connection of the event source
	 */
	public function close();
}
