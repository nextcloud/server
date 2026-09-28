<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesReminders\Listener;

use OCA\DAV\Events\SabrePluginAddEvent;
use OCA\FilesReminders\Dav\PropFindPlugin;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Container\ContainerInterface;

/** @template-implements IEventListener<SabrePluginAddEvent> */
class SabrePluginAddListener implements IEventListener {
	public function __construct(
		private ContainerInterface $container,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof SabrePluginAddEvent)) {
			return;
		}

		$server = $event->getServer();
		$plugin = $this->container->get(PropFindPlugin::class);
		$server->addPlugin($plugin);
	}
}
