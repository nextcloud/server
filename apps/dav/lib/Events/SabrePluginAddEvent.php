<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;
use Sabre\DAV\Server;

/**
 * This event is triggered during the setup of the SabreDAV server to allow the
 * registration of additional plugins.
 *
 * @since 28.0.0
 */
class SabrePluginAddEvent extends Event {

	/** @var Server */
	private $server;

	/**
	 * @since 28.0.0
	 */
	public function __construct(Server $server) {
		parent::__construct();
		$this->server = $server;
	}

	/**
	 * @since 28.0.0
	 */
	public function getServer(): Server {
		return $this->server;
	}
}
