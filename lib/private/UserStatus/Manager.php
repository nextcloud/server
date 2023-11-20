<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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

	public function setUserStatus(string $userId, string $messageId, string $status, bool $createBackup = false): void {
		$this->setupProvider();
		if (!$this->provider || !($this->provider instanceof ISettableProvider)) {
			return;
		}

		$this->provider->setUserStatus($userId, $messageId, $status, $createBackup);
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
