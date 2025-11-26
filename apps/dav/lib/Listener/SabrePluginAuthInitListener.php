<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Listener;

use OCA\DAV\CalDAV\Federation\CalendarFederationConfig;
use OCA\DAV\CalDAV\Federation\FederatedCalendarAuth;
use OCA\DAV\Events\SabrePluginAuthInitEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Server;
use Sabre\DAV\Auth\Plugin;

/**
 * @template-implements IEventListener<Event|SabrePluginAuthInitEvent>
 */
class SabrePluginAuthInitListener implements IEventListener {
	public function __construct(
		private readonly CalendarFederationConfig $calendarFederationConfig,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof SabrePluginAuthInitEvent)) {
			return;
		}

		if (!$this->calendarFederationConfig->isFederationEnabled()) {
			return;
		}

		$server = $event->getServer();
		$authPlugin = $server->getPlugin('auth');
		if ($authPlugin instanceof Plugin) {
			$authBackend = Server::get(FederatedCalendarAuth::class);
			$authPlugin->addBackend($authBackend);
		}
	}
}
