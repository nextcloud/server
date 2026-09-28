<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Listener;

use OCA\Files\Event\LoadSearchPlugins;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

/** @template-implements IEventListener<LoadSearchPlugins> */
class LoadSearchPluginsListener implements IEventListener {
	public function handle(Event $event): void {
		if (!$event instanceof LoadSearchPlugins) {
			return;
		}

		Util::addScript('files', 'search');
	}
}
