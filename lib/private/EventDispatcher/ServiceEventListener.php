<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\EventDispatcher;

use OCP\AppFramework\QueryException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IServerContainer;
use Psr\Log\LoggerInterface;
use function sprintf;

/**
 * Lazy service event listener
 *
 * Makes it possible to lazy-route a dispatched event to a service instance
 * created by the service container
 */
final class ServiceEventListener {
	/** @var IServerContainer */
	private $container;

	/** @var string */
	private $class;

	/** @var LoggerInterface */
	private $logger;

	/** @var null|IEventListener */
	private $service;

	public function __construct(IServerContainer $container,
								string $class,
								LoggerInterface $logger) {
		$this->container = $container;
		$this->class = $class;
		$this->logger = $logger;
	}

	public function __invoke(Event $event) {
		if ($this->service === null) {
			try {
				// TODO: fetch from the app containers, otherwise any custom services,
				//       parameters and aliases won't be resolved.
				//       See https://github.com/nextcloud/server/issues/27793 for details.
				$this->service = $this->container->query($this->class);
			} catch (QueryException $e) {
				$this->logger->error(
					sprintf(
						'Could not load event listener service %s: %s. Make sure the class is auto-loadable by the Nextcloud server container',
						$this->class,
						$e->getMessage()
					),
					[
						'exception' => $e,
					]
				);
				return;
			}
		}

		$this->service->handle($event);
	}
}
