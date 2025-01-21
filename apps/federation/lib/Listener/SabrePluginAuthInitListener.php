<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Federation\Listener;

use OCA\DAV\Events\SabrePluginAuthInitEvent;
use OCA\Federation\DAV\FedAuth;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Sabre\DAV\Auth\Plugin;

/**
 * @since 20.0.0
 * @template-implements IEventListener<SabrePluginAuthInitEvent>
 */
class SabrePluginAuthInitListener implements IEventListener {
	public function __construct(
		private FedAuth $fedAuth,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof SabrePluginAuthInitEvent)) {
			return;
		}

		$server = $event->getServer();
		$authPlugin = $server->getPlugin('auth');
		if ($authPlugin instanceof Plugin) {
			$authPlugin->addBackend($this->fedAuth);
		}
	}
}
