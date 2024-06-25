<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Log\BeforeMessageLoggedEvent;

/**
 * Listen to log calls and output them to STDOUT for debug purposes
 * @template-implements IEventListener<BeforeMessageLoggedEvent>
 */
class BeforeMessageLoggedEventListener implements IEventListener {
	public function handle(Event $event): void {
		if (!$event instanceof BeforeMessageLoggedEvent) {
			return;
		}
		echo
			match($event->getLevel()) {
				0 => '[debug]',
				1 => '[info]',
				2 => '[warning]',
				3 => '[error]',
				4 => '[fatal]',
				default => '['.$event->getLevel().']',
			}
		.' ['.$event->getApp().'] '
		.$event->getMessage()['message']
		."\n";
	}
}
