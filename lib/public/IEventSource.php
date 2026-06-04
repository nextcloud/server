<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP;

/**
 * Wrapper for Server-Sent Events (SSE).
 *
 * Use SSE with caution: too many concurrent open requests can overload or stall the server.
 *
 * The connection is opened lazily when the first event is sent.
 *
 * @see https://developer.mozilla.org/docs/Web/API/Server-sent_events
 * @since 8.0.0
 */
interface IEventSource {
	/**
	 * Sends an event to the client.
	 *
	 * @param string $type Event type/name.
	 * @param mixed $data Event payload.
	 *
	 * If only one argument is provided, it is sent as a typeless payload (legacy behavior).
	 * @since 8.0.0
	 */
	public function send(string $type, mixed $data = null): void;

	/**
	 * Closes the SSE connection.
	 *
	 * @since 8.0.0
	 */
	public function close(): void;
}
