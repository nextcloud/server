<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCP\Console\ConsoleEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<ConsoleEvent>
 */
class ConsoleEventListener extends Action implements IEventListener {
	public function handle(Event $event): void {
		if ($event instanceof ConsoleEvent) {
			$this->runCommand($event);
		}
	}

	private function runCommand(ConsoleEvent $event): void {
		$arguments = $event->getArguments();
		if (!isset($arguments[1]) || $arguments[1] === '_completion') {
			// Don't log autocompletion
			return;
		}

		// Remove `./occ`
		array_shift($arguments);

		$this->log('Console command executed: %s',
			['arguments' => implode(' ', $arguments)],
			['arguments']
		);
	}
}
