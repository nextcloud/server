<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Listener;

use OCP\Console\ReservedOptions;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Log\BeforeMessageLoggedEvent;
use OCP\Server;

/**
 * Listen to log calls and output them to STDOUT for debug purposes
 * @template-implements IEventListener<BeforeMessageLoggedEvent>
 */
class BeforeMessageLoggedEventListener implements IEventListener {
	public function __construct(
		private int $level,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof BeforeMessageLoggedEvent) {
			return;
		}
		if ($event->getLevel() < $this->level) {
			return;
		}
		echo
			match($event->getLevel()) {
				0 => '[debug]',
				1 => '[info]',
				2 => '[warning]',
				3 => '[error]',
				4 => '[fatal]',
				default => '[' . $event->getLevel() . ']',
			}
		. ' [' . $event->getApp() . '] '
		. $event->getMessage()['message']
		. "\n";
	}

	/**
	 * Register listener to log messages and remove debug options from $_SERVER['argv']
	 */
	public static function setup(): void {
		$eventDispatcher = Server::get(IEventDispatcher::class);
		$argv = $_SERVER['argv'];
		$level = 0;
		foreach ($argv as $key => $arg) {
			if ($arg === '--' . ReservedOptions::DEBUG_LOG) {
				unset($argv[$key]);
			} elseif (str_starts_with($arg, '--' . ReservedOptions::DEBUG_LOG_LEVEL . '=')) {
				$level = (int)substr($arg, strlen('--' . ReservedOptions::DEBUG_LOG_LEVEL . '='));
				unset($argv[$key]);
			}
		}
		$_SERVER['argv'] = array_values($argv);
		$debugLoggerEventListener = new self($level);
		$eventDispatcher->addListener(BeforeMessageLoggedEvent::class, $debugLoggerEventListener->handle(...));
	}
}
