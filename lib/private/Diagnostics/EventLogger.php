<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Piotr Mrówczyński <mrow4a@yahoo.com>
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Diagnostics;

use OC\Log;
use OC\SystemConfig;
use OCP\Diagnostics\IEvent;
use OCP\Diagnostics\IEventLogger;
use Psr\Log\LoggerInterface;

class EventLogger implements IEventLogger {
	/** @var Event[] */
	private $events = [];

	/** @var SystemConfig */
	private $config;

	/** @var LoggerInterface */
	private $logger;

	/** @var Log */
	private $internalLogger;

	/**
	 * @var bool - Module needs to be activated by some app
	 */
	private $activated = false;

	public function __construct(SystemConfig $config, LoggerInterface $logger, Log $internalLogger) {
		$this->config = $config;
		$this->logger = $logger;
		$this->internalLogger = $internalLogger;

		if ($this->isLoggingActivated()) {
			$this->activate();
		}
	}

	public function isLoggingActivated(): bool {
		$systemValue = (bool)$this->config->getValue('diagnostics.logging', false)
			|| (bool)$this->config->getValue('profiler', false);

		if ($systemValue && $this->config->getValue('debug', false)) {
			return true;
		}

		$isDebugLevel = $this->internalLogger->getLogLevel([]) === Log::DEBUG;
		return $systemValue && $isDebugLevel;
	}

	/**
	 * @inheritdoc
	 */
	public function start($id, $description = '') {
		if ($this->activated) {
			$this->events[$id] = new Event($id, $description, microtime(true));
			$this->writeLog($this->events[$id]);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function end($id) {
		if ($this->activated && isset($this->events[$id])) {
			$timing = $this->events[$id];
			$timing->end(microtime(true));
			$this->writeLog($timing);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function log($id, $description, $start, $end) {
		if ($this->activated) {
			$this->events[$id] = new Event($id, $description, $start);
			$this->events[$id]->end($end);
			$this->writeLog($this->events[$id]);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getEvents() {
		return $this->events;
	}

	/**
	 * @inheritdoc
	 */
	public function activate() {
		$this->activated = true;
	}

	private function writeLog(IEvent $event) {
		if ($this->activated) {
			if ($event->getEnd() === null) {
				return;
			}
			$duration = $event->getDuration();
			$timeInMs = round($duration * 1000, 4);

			$loggingMinimum = (int)$this->config->getValue('diagnostics.logging.threshold', 0);
			if ($loggingMinimum === 0 || $timeInMs < $loggingMinimum) {
				return;
			}

			$message = microtime() . ' - ' . $event->getId() . ': ' . $timeInMs . ' (' . $event->getDescription() . ')';
			$this->logger->debug($message, ['app' => 'diagnostics']);
		}
	}
}
