<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\UserStatus;

use OCP\IServerContainer;
use OCP\UserStatus\IManager;
use OCP\UserStatus\IProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;

class Manager implements IManager {
	/** @var IServerContainer */
	private $container;

	/** @var LoggerInterface */
	private $logger;

	/** @var class-string */
	private $providerClass;

	/** @var IProvider */
	private $provider;

	/**
	 * Manager constructor.
	 *
	 * @param IServerContainer $container
	 * @param LoggerInterface $logger
	 */
	public function __construct(IServerContainer $container,
		LoggerInterface $logger) {
		$this->container = $container;
		$this->logger = $logger;
	}

	/**
	 * @inheritDoc
	 */
	public function getUserStatuses(array $userIds): array {
		$this->setupProvider();
		if (!$this->provider) {
			return [];
		}

		return $this->provider->getUserStatuses($userIds);
	}

	/**
	 * @param string $class
	 * @since 20.0.0
	 * @internal
	 */
	public function registerProvider(string $class): void {
		$this->providerClass = $class;
		$this->provider = null;
	}

	/**
	 * Lazily set up provider
	 */
	private function setupProvider(): void {
		if ($this->provider !== null) {
			return;
		}
		if ($this->providerClass === null) {
			return;
		}

		/**
		 * @psalm-suppress InvalidCatch
		 */
		try {
			$provider = $this->container->get($this->providerClass);
		} catch (ContainerExceptionInterface $e) {
			$this->logger->error('Could not load user-status "' . $this->providerClass . '" provider dynamically: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return;
		}

		$this->provider = $provider;
	}

	public function setUserStatus(string $userId, string $messageId, string $status, bool $createBackup = false, ?string $customMessage = null): void {
		$this->setupProvider();
		if (!$this->provider || !($this->provider instanceof ISettableProvider)) {
			return;
		}

		$this->provider->setUserStatus($userId, $messageId, $status, $createBackup, $customMessage);
	}

	public function revertUserStatus(string $userId, string $messageId, string $status): void {
		$this->setupProvider();
		if (!$this->provider || !($this->provider instanceof ISettableProvider)) {
			return;
		}
		$this->provider->revertUserStatus($userId, $messageId, $status);
	}

	public function revertMultipleUserStatus(array $userIds, string $messageId, string $status): void {
		$this->setupProvider();
		if (!$this->provider || !($this->provider instanceof ISettableProvider)) {
			return;
		}
		$this->provider->revertMultipleUserStatus($userIds, $messageId, $status);
	}
}
