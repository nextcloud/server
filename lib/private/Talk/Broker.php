<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OC\Talk;

use OC\AppFramework\Bootstrap\Coordinator;
use OCP\IServerContainer;
use OCP\Talk\Exceptions\NoBackendException;
use OCP\Talk\IBroker;
use OCP\Talk\IConversation;
use OCP\Talk\IConversationOptions;
use OCP\Talk\ITalkBackend;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class Broker implements IBroker {
	private Coordinator $coordinator;

	private IServerContainer $container;

	private LoggerInterface $logger;

	private ?bool $hasBackend = null;

	private ?ITalkBackend $backend = null;

	public function __construct(Coordinator $coordinator,
		IServerContainer $container,
		LoggerInterface $logger) {
		$this->coordinator = $coordinator;
		$this->container = $container;
		$this->logger = $logger;
	}

	public function hasBackend(): bool {
		if ($this->hasBackend !== null) {
			return $this->hasBackend;
		}

		$context = $this->coordinator->getRegistrationContext();
		if ($context === null) {
			// Backend requested too soon, e.g. from the bootstrap `register` method of an app
			throw new RuntimeException("Not all apps have been registered yet");
		}
		$backendRegistration = $context->getTalkBackendRegistration();
		if ($backendRegistration === null) {
			// Nothing to do. Remember and exit.
			return $this->hasBackend = false;
		}

		try {
			$this->backend = $this->container->get(
				$backendRegistration->getService()
			);

			// Remember and return
			return $this->hasBackend = true;
		} catch (Throwable $e) {
			$this->logger->error("Talk backend {class} could not be loaded: " . $e->getMessage(), [
				'class' => $backendRegistration->getService(),
				'exception' => $e,
			]);

			// Temporary result. Maybe the next time the backend is requested it can be loaded.
			return false;
		}
	}

	public function newConversationOptions(): IConversationOptions {
		return ConversationOptions::default();
	}

	public function createConversation(string $name,
		array $moderators,
		IConversationOptions $options = null): IConversation {
		if (!$this->hasBackend()) {
			throw new NoBackendException("The Talk broker has no registered backend");
		}

		return $this->backend->createConversation(
			$name,
			$moderators,
			$options ?? ConversationOptions::default()
		);
	}

	public function deleteConversation(string $id): void {
		if (!$this->hasBackend()) {
			throw new NoBackendException("The Talk broker has no registered backend");
		}

		$this->backend->deleteConversation($id);
	}
}
