<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Share20;

use OC\Share20\Exception\ProviderException;
use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\FederatedFileSharing\Notifications;
use OCA\FederatedFileSharing\TokenHandler;
use OCA\ShareByMail\Settings\SettingsManager;
use OCA\ShareByMail\ShareByMailProvider;
use OCA\Talk\Share\RoomShareProvider;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudFederationFactory;
use OCP\Files\IRootFolder;
use OCP\Http\Client\IClientService;
use OCP\IServerContainer;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;
use OCP\Share\IManager;
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
	/** @var IServerContainer */
	private $serverContainer;
	/** @var DefaultShareProvider */
	private $defaultProvider = null;
	/** @var FederatedShareProvider */
	private $federatedProvider = null;
	/** @var  ShareByMailProvider */
	private $shareByMailProvider;
	/** @var  \OCA\Circles\ShareByCircleProvider */
	private $shareByCircleProvider = null;
	/** @var bool */
	private $circlesAreNotAvailable = false;
	/** @var \OCA\Talk\Share\RoomShareProvider */
	private $roomShareProvider = null;

	private $registeredShareProviders = [];

	private $shareProviders = [];

	/**
	 * IProviderFactory constructor.
	 *
	 * @param IServerContainer $serverContainer
	 */
	public function __construct(IServerContainer $serverContainer) {
		$this->serverContainer = $serverContainer;
	}

	public function registerProvider(string $shareProviderClass): void {
		$this->registeredShareProviders[] = $shareProviderClass;
	}

	/**
	 * Create the default share provider.
	 *
	 * @return DefaultShareProvider
	 */
	protected function defaultShareProvider() {
		if ($this->defaultProvider === null) {
			$this->defaultProvider = new DefaultShareProvider(
				$this->serverContainer->getDatabaseConnection(),
				$this->serverContainer->getUserManager(),
				$this->serverContainer->getGroupManager(),
				$this->serverContainer->get(IRootFolder::class),
				$this->serverContainer->get(IMailer::class),
				$this->serverContainer->query(Defaults::class),
				$this->serverContainer->get(IFactory::class),
				$this->serverContainer->getURLGenerator(),
				$this->serverContainer->query(ITimeFactory::class),
			);
		}

		return $this->defaultProvider;
	}

	/**
	 * Create the federated share provider
	 *
	 * @return FederatedShareProvider
	 */
	protected function federatedShareProvider() {
		if ($this->federatedProvider === null) {
			/*
			 * Check if the app is enabled
			 */
			$appManager = $this->serverContainer->getAppManager();
			if (!$appManager->isEnabledForUser('federatedfilesharing')) {
				return null;
			}

			/*
			 * TODO: add factory to federated sharing app
			 */
			$l = $this->serverContainer->getL10N('federatedfilesharing');
			$addressHandler = new AddressHandler(
				$this->serverContainer->getURLGenerator(),
				$l,
				$this->serverContainer->getCloudIdManager()
			);
			$notifications = new Notifications(
				$addressHandler,
				$this->serverContainer->get(IClientService::class),
				$this->serverContainer->query(\OCP\OCS\IDiscoveryService::class),
				$this->serverContainer->getJobList(),
				\OC::$server->getCloudFederationProviderManager(),
				\OC::$server->get(ICloudFederationFactory::class),
				$this->serverContainer->query(IEventDispatcher::class),
				$this->serverContainer->get(LoggerInterface::class),
			);
			$tokenHandler = new TokenHandler(
				$this->serverContainer->get(ISecureRandom::class)
			);

			$this->federatedProvider = new FederatedShareProvider(
				$this->serverContainer->getDatabaseConnection(),
				$addressHandler,
				$notifications,
				$tokenHandler,
				$l,
				$this->serverContainer->get(IRootFolder::class),
				$this->serverContainer->getConfig(),
				$this->serverContainer->getUserManager(),
				$this->serverContainer->getCloudIdManager(),
				$this->serverContainer->getGlobalScaleConfig(),
				$this->serverContainer->getCloudFederationProviderManager(),
				$this->serverContainer->get(LoggerInterface::class),
			);
		}

		return $this->federatedProvider;
	}

	/**
	 * Create the federated share provider
	 *
	 * @return ShareByMailProvider
	 */
	protected function getShareByMailProvider() {
		if ($this->shareByMailProvider === null) {
			/*
			 * Check if the app is enabled
			 */
			$appManager = $this->serverContainer->getAppManager();
			if (!$appManager->isEnabledForUser('sharebymail')) {
				return null;
			}

			$settingsManager = new SettingsManager($this->serverContainer->getConfig());

			$this->shareByMailProvider = new ShareByMailProvider(
				$this->serverContainer->getConfig(),
				$this->serverContainer->getDatabaseConnection(),
				$this->serverContainer->get(ISecureRandom::class),
				$this->serverContainer->getUserManager(),
				$this->serverContainer->get(IRootFolder::class),
				$this->serverContainer->getL10N('sharebymail'),
				$this->serverContainer->get(LoggerInterface::class),
				$this->serverContainer->get(IMailer::class),
				$this->serverContainer->getURLGenerator(),
				$this->serverContainer->getActivityManager(),
				$settingsManager,
				$this->serverContainer->query(Defaults::class),
				$this->serverContainer->get(IHasher::class),
				$this->serverContainer->get(IEventDispatcher::class),
				$this->serverContainer->get(IManager::class)
			);
		}

		return $this->shareByMailProvider;
	}


	/**
	 * Create the circle share provider
	 *
	 * @return FederatedShareProvider
	 *
	 * @suppress PhanUndeclaredClassMethod
	 */
	protected function getShareByCircleProvider() {
		if ($this->circlesAreNotAvailable) {
			return null;
		}

		if (!$this->serverContainer->getAppManager()->isEnabledForUser('circles') ||
			!class_exists('\OCA\Circles\ShareByCircleProvider')
		) {
			$this->circlesAreNotAvailable = true;
			return null;
		}

		if ($this->shareByCircleProvider === null) {
			$this->shareByCircleProvider = new \OCA\Circles\ShareByCircleProvider(
				$this->serverContainer->getDatabaseConnection(),
				$this->serverContainer->get(ISecureRandom::class),
				$this->serverContainer->getUserManager(),
				$this->serverContainer->get(IRootFolder::class),
				$this->serverContainer->getL10N('circles'),
				$this->serverContainer->getLogger(),
				$this->serverContainer->getURLGenerator()
			);
		}

		return $this->shareByCircleProvider;
	}

	/**
	 * Create the room share provider
	 *
	 * @return RoomShareProvider
	 */
	protected function getRoomShareProvider() {
		if ($this->roomShareProvider === null) {
			/*
			 * Check if the app is enabled
			 */
			$appManager = $this->serverContainer->getAppManager();
			if (!$appManager->isEnabledForUser('spreed')) {
				return null;
			}

			try {
				/**
				 * @psalm-suppress UndefinedClass
				 */
				$this->roomShareProvider = $this->serverContainer->get(RoomShareProvider::class);
			} catch (\Throwable $e) {
				$this->serverContainer->get(LoggerInterface::class)->error(
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
		} elseif ($id === 'ocCircleShare') {
			$provider = $this->getShareByCircleProvider();
		} elseif ($id === 'ocRoomShare') {
			$provider = $this->getRoomShareProvider();
		}

		foreach ($this->registeredShareProviders as $shareProvider) {
			try {
				/** @var IShareProvider $instance */
				$instance = $this->serverContainer->get($shareProvider);
				$this->shareProviders[$instance->identifier()] = $instance;
			} catch (\Throwable $e) {
				$this->serverContainer->get(LoggerInterface::class)->error(
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

		if ($shareType === IShare::TYPE_USER ||
			$shareType === IShare::TYPE_GROUP ||
			$shareType === IShare::TYPE_LINK
		) {
			$provider = $this->defaultShareProvider();
		} elseif ($shareType === IShare::TYPE_REMOTE || $shareType === IShare::TYPE_REMOTE_GROUP) {
			$provider = $this->federatedShareProvider();
		} elseif ($shareType === IShare::TYPE_EMAIL) {
			$provider = $this->getShareByMailProvider();
		} elseif ($shareType === IShare::TYPE_CIRCLE) {
			$provider = $this->getShareByCircleProvider();
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
		$shareByCircle = $this->getShareByCircleProvider();
		if ($shareByCircle !== null) {
			$shares[] = $shareByCircle;
		}
		$roomShare = $this->getRoomShareProvider();
		if ($roomShare !== null) {
			$shares[] = $roomShare;
		}

		foreach ($this->registeredShareProviders as $shareProvider) {
			try {
				/** @var IShareProvider $instance */
				$instance = $this->serverContainer->get($shareProvider);
			} catch (\Throwable $e) {
				$this->serverContainer->get(LoggerInterface::class)->error(
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
