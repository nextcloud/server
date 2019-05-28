<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OC\EventDispatcher;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IContainer;
use OCP\ILogger;
use OCP\IServerContainer;
use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyDispatcher;

class EventDispatcher implements IEventDispatcher {

	/** @var SymfonyDispatcher */
	private $dispatcher;

	/** @var IContainer */
	private $container;

	/** @var ILogger */
	private $logger;

	public function __construct(SymfonyDispatcher $dispatcher,
								IServerContainer $container,
								ILogger $logger) {
		$this->dispatcher = $dispatcher;
		$this->container = $container;
		$this->logger = $logger;
	}

	public function addListener(string $eventName,
								callable $listener,
								int $priority = 0): void {
		$this->dispatcher->addListener($eventName, $listener, $priority);
	}

	public function addServiceListener(string $eventName,
									   string $className,
									   int $priority = 0): void {
		$listener = new ServiceEventListener(
			$this->container,
			$className,
			$this->logger
		);

		$this->addListener($eventName, $listener, $priority);
	}

	public function dispatch(string $eventName,
							 Event $event): void {

		$this->dispatcher->dispatch($eventName, $event);
	}

	/**
	 * @return SymfonyDispatcher
	 */
	public function getSymfonyDispatcher(): SymfonyDispatcher {
		return $this->dispatcher;
	}

}
