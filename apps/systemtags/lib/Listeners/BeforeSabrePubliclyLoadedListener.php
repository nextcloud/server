<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\SystemTags\Listeners;

use OCA\DAV\SystemTag\SystemTagPlugin;
use OCP\BeforeSabrePubliclyLoadedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Server;

/**
 * @template-implements IEventListener<BeforeSabrePubliclyLoadedEvent>
 */
class BeforeSabrePubliclyLoadedListener implements IEventListener {
	public function handle(Event $event): void {
		if (!$event instanceof BeforeSabrePubliclyLoadedEvent) {
			return;
		}

		$server = $event->getServer();
		if ($server === null) {
			return;
		}

		$server->addPlugin(Server::get(SystemTagPlugin::class));
	}
}
