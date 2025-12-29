<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Listener;

use OCP\Console\ReservedOptions;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;
use OCP\Log\BeforeMessageLoggedEvent;
use OCP\Server;

/**
 * Event listener that outputs log messages to STDOUT for debugging in CLI context.
 * Activated with debug CLI options and safe for use during command-line development.
 *
 * After processing debug flags, cleans up CLI arguments to avoid interfering with other handlers.
 *
 * @template-implements IEventListener<BeforeMessageLoggedEvent>
 */
class BeforeMessageLoggedEventListener implements IEventListener {
	public function __construct(
		private int $level,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeMessageLoggedEvent)) {
			return;
		}

		if ($event->getLevel() < $this->level) {
			return;
		}

		if (PHP_SAPI !== 'cli') {
			return;
		}

		echo $this->formatLogLine($event);
	}

	private function formatLogLine(BeforeMessageLoggedEvent $event): string {
		$level = $event->getLevel();
		$levelStr = match($level) {
			ILogger::DEBUG => '[debug]',
			ILogger::INFO => '[info]',
			ILogger::WARN => '[warning]',
			ILogger::ERROR => '[error]',
			ILogger::FATAL => '[fatal]',
			default => '[' . $level . ']',
		};
		$app = $event->getApp();
		$msg = $event->getMessage()['message'] ?? '';
		return sprintf("%s [%s] %s\n", $levelStr, $app, $msg);
	}

	/**
	 * Register listener to log messages and remove debug options from $_SERVER['argv']
	 *
	 * The debug CLI options (e.g., --debug-log and --debug-log-level=)
	 * are used solely by this listener for runtime log output control.
	 *
	 * After parsing and using them, they are removed from $_SERVER['argv']
	 * so that other CLI components (such as Symfony Console) do not see
	 * unrecognized options, preventing errors or accidental exposure of internal flags.
	 */
	public static function setup(): void {
		$eventDispatcher = Server::get(IEventDispatcher::class);

		/** @psalm-suppress TypeDoesNotContainType */
		if (!isset($_SERVER['argv']) || !is_array($_SERVER['argv'])) {
			// Likely reached here outside of CLI mode somehow
			return;
		}

		$argv = $_SERVER['argv'];
		$level = ILogger::DEBUG;

		foreach ($argv as $key => $arg) {
			// Remove debug option(s) from the CLI arguments after using, so that other
			// parts of the CLI framework do not encounter unknown options.
			if ($arg === '--' . ReservedOptions::DEBUG_LOG) {
				unset($argv[$key]);
			} elseif (str_starts_with($arg, '--' . ReservedOptions::DEBUG_LOG_LEVEL . '=')) {
				$level = (int)substr($arg, strlen('--' . ReservedOptions::DEBUG_LOG_LEVEL . '='));
				unset($argv[$key]);
			}
		}
		$_SERVER['argv'] = array_values($argv);

		// create a new instance of the current class and pass the desired log $level (from the CLI) options
		$debugLoggerEventListener = new self($level);
		// register a new event listener for the BeforeMessageLoggedEvent
		$eventDispatcher->addListener(BeforeMessageLoggedEvent::class, $debugLoggerEventListener->handle(...));
	}
}
