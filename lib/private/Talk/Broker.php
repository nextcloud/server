<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
			throw new RuntimeException('Not all apps have been registered yet');
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
			$this->logger->error('Talk backend {class} could not be loaded: ' . $e->getMessage(), [
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
		?IConversationOptions $options = null): IConversation {
		if (!$this->hasBackend()) {
			throw new NoBackendException('The Talk broker has no registered backend');
		}

		return $this->backend->createConversation(
			$name,
			$moderators,
			$options ?? ConversationOptions::default()
		);
	}

	public function deleteConversation(string $id): void {
		if (!$this->hasBackend()) {
			throw new NoBackendException('The Talk broker has no registered backend');
		}

		$this->backend->deleteConversation($id);
	}
}
