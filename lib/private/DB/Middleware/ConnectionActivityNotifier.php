<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\Middleware;

/**
 * Relays driver level activity to a listener that can only be registered
 * after the middleware was created: middlewares are configured before the
 * DriverManager constructs the connection wrapper that wants to listen.
 */
final class ConnectionActivityNotifier {
	private ?\Closure $listener = null;

	/**
	 * @param \Closure():void $listener
	 */
	public function setListener(\Closure $listener): void {
		$this->listener = $listener;
	}

	public function notify(): void {
		if ($this->listener !== null) {
			($this->listener)();
		}
	}
}
