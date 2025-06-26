<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Share20;

use OC\Share20\Exception\ProviderException;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\ShareByMail\ShareByMailProvider;
use OCA\Talk\Share\RoomShareProvider;
use OCP\App\IAppManager;
use OCP\Server;
use OCP\Share\IProviderFactory;
use OCP\Share\IShare;
use OCP\Share\IShareProvider;
use Psr\Log\LoggerInterface;

/**
 * Class ProviderFactory
 *
 * @package OC\Share20
 */
class ProviderFactory implements IProviderFactory {
	private ?DefaultShareProvider $defaultProvider = null;
	private ?FederatedShareProvider $federatedProvider = null;
	private ?ShareByMailProvider $shareByMailProvider = null;
	/**
	 * @psalm-suppress UndefinedDocblockClass
	 * @var ?RoomShareProvider
	 */
	private $roomShareProvider = null;

	private array $registeredShareProviders = [];

	private array $shareProviders = [];

	public function __construct(
		protected IAppManager $appManager,
		protected LoggerInterface $logger,
	) {
	}

	public function registerProvider(string $shareProviderClass): void {
		$this->registeredShareProviders[] = $shareProviderClass;
	}

	/**
	 * Create the default share provider.
	 */
	protected function defaultShareProvider(): DefaultShareProvider {
		return Server::get(DefaultShareProvider::class);
	}

	/**
	 * Create the federated share provider
	 */
	protected function federatedShareProvider(): ?FederatedShareProvider {
		if ($this->federatedProvider === null) {
			/*
			 * Check if the app is enabled
			 */
			if (!$this->appManager->isEnabledForUser('federatedfilesharing')) {
				return null;
			}

			$this->federatedProvider = Server::get(FederatedShareProvider::class);
		}

		return $this->federatedProvider;
	}

	/**
	 * Create the federated share provider
	 */
	protected function getShareByMailProvider(): ?ShareByMailProvider {
		if ($this->shareByMailProvider === null) {
			/*
			 * Check if the app is enabled
			 */
			if (!$this->appManager->isEnabledForUser('sharebymail')) {
				return null;
			}

			$this->shareByMailProvider = Server::get(ShareByMailProvider::class);
		}

		return $this->shareByMailProvider;
	}

	/**
	 * Create the room share provider
	 *
	 * @psalm-suppress UndefinedDocblockClass
	 * @return ?RoomShareProvider
	 */
	protected function getRoomShareProvider() {
		if ($this->roomShareProvider === null) {
			/*
			 * Check if the app is enabled
			 */
			if (!$this->appManager->isEnabledForUser('spreed')) {
				return null;
			}

			try {
				/**
				 * @psalm-suppress UndefinedClass
				 */
				$this->roomShareProvider = Server::get(RoomShareProvider::class);
			} catch (\Throwable $e) {
				$this->logger->error(
					$e->getMessage(),
					['exception' => $e]
				);
				return null;
			}
		}

		return $this->roomShareProvider;
	}

	/**
	 * @inheritdoc
	 */
	public function getProvider($id) {
		$provider = null;
		if (isset($this->shareProviders[$id])) {
			return $this->shareProviders[$id];
		}

		if ($id === 'ocinternal') {
			$provider = $this->defaultShareProvider();
		} elseif ($id === 'ocFederatedSharing') {
			$provider = $this->federatedShareProvider();
		} elseif ($id === 'ocMailShare') {
			$provider = $this->getShareByMailProvider();
		} elseif ($id === 'ocRoomShare') {
			$provider = $this->getRoomShareProvider();
		}

		foreach ($this->registeredShareProviders as $shareProvider) {
			try {
				/** @var IShareProvider $instance */
				$instance = Server::get($shareProvider);
				$this->shareProviders[$instance->identifier()] = $instance;
			} catch (\Throwable $e) {
				$this->logger->error(
					$e->getMessage(),
					['exception' => $e]
				);
			}
		}

		if (isset($this->shareProviders[$id])) {
			$provider = $this->shareProviders[$id];
		}

		if ($provider === null) {
			throw new ProviderException('No provider with id .' . $id . ' found.');
		}

		return $provider;
	}

	/**
	 * @inheritdoc
	 */
	public function getProviderForType($shareType) {
		$provider = null;

		if ($shareType === IShare::TYPE_USER
			|| $shareType === IShare::TYPE_GROUP
			|| $shareType === IShare::TYPE_LINK
		) {
			$provider = $this->defaultShareProvider();
		} elseif ($shareType === IShare::TYPE_REMOTE || $shareType === IShare::TYPE_REMOTE_GROUP) {
			$provider = $this->federatedShareProvider();
		} elseif ($shareType === IShare::TYPE_EMAIL) {
			$provider = $this->getShareByMailProvider();
		} elseif ($shareType === IShare::TYPE_CIRCLE) {
			$provider = $this->getProvider('ocCircleShare');
		} elseif ($shareType === IShare::TYPE_ROOM) {
			$provider = $this->getRoomShareProvider();
		} elseif ($shareType === IShare::TYPE_DECK) {
			$provider = $this->getProvider('deck');
		} elseif ($shareType === IShare::TYPE_SCIENCEMESH) {
			$provider = $this->getProvider('sciencemesh');
		}


		if ($provider === null) {
			throw new ProviderException('No share provider for share type ' . $shareType);
		}

		return $provider;
	}

	public function getAllProviders() {
		$shares = [$this->defaultShareProvider(), $this->federatedShareProvider()];
		$shareByMail = $this->getShareByMailProvider();
		if ($shareByMail !== null) {
			$shares[] = $shareByMail;
		}
		$roomShare = $this->getRoomShareProvider();
		if ($roomShare !== null) {
			$shares[] = $roomShare;
		}

		foreach ($this->registeredShareProviders as $shareProvider) {
			try {
				/** @var IShareProvider $instance */
				$instance = Server::get($shareProvider);
			} catch (\Throwable $e) {
				$this->logger->error(
					$e->getMessage(),
					['exception' => $e]
				);
				continue;
			}

			if (!isset($this->shareProviders[$instance->identifier()])) {
				$this->shareProviders[$instance->identifier()] = $instance;
			}
			$shares[] = $this->shareProviders[$instance->identifier()];
		}



		return $shares;
	}
}
