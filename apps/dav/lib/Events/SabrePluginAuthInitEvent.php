<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;
use Sabre\DAV\Server;

/**
 * This event is triggered during the setup of the SabreDAV server to allow the
 * registration of additional authentication backends.
 *
 * @since 20.0.0
 */
class SabrePluginAuthInitEvent extends Event {

	/**
	 * @since 20.0.0
	 */
	public function __construct(
		private Server $server,
	) {
	}

	/**
	 * @since 20.0.0
	 */
	public function getServer(): Server {
		return $this->server;
	}
}
