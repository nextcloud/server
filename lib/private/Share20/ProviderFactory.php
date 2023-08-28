<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Maxence Lange <maxence@nextcloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Samuel <faust64@gmail.com>
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
namespace OC\Share20;

use OC\AllConfig;
use OC\Share20\Exception\ProviderException;
use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\FederatedFileSharing\Notifications;
use OCA\FederatedFileSharing\TokenHandler;
use OCA\ShareByMail\Settings\SettingsManager;
use OCA\ShareByMail\ShareByMailProvider;
use OCA\Talk\Share\RoomShareProvider;
use OCP\Activity\IManager as IActivityManager;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudIdManager;
use OCP\Files\IRootFolder;
use OCP\GlobalScale\IConfig;
use OCP\Http\Client\IClientService;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IProviderFactory;
use OCP\Share\IShare;
use OCP\Share\IShareProvider;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ProviderFactory
 *
 * @package OC\Share20
 */
class ProviderFactory implements IProviderFactory {
	/** @var ContainerInterface */
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
	 * @param ContainerInterface $serverContainer
	 */
	public function __construct(ContainerInterface $serverContainer) {
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
				$this->serverContainer->get(IDBConnection::class),
				$this->serverContainer->get(IUserManager::class),
				$this->serverContainer->get(IGroupManager::class),
				$this->serverContainer->get(IRootFolder::class),
				$this->serverContainer->get(IMailer::class),
				$this->serverContainer->get(Defaults::class),
				$this->serverContainer->get(IL10NFactory::class),
				$this->serverContainer->get(IURLGenerator::class),
				$this->serverContainer->get(AllConfig::class)
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
			$appManager = $this->serverContainer->get(IAppManager::class);
			if (!$appManager->isEnabledForUser('federatedfilesharing')) {
				return null;
			}

			/*
			 * TODO: add factory to federated sharing app
			 */
			$l = $this->serverContainer->get(IFactory::class)->get('federatedfilesharing');
			$addressHandler = new AddressHandler(
				$this->serverContainer->get(IURLGenerator::class),
				$l,
				$this->serverContainer->get(ICloudIdManager::class)
			);
			$notifications = new Notifications(
				$addressHandler,
				$this->serverContainer->get(IClientService::class),
				$this->serverContainer->get(\OCP\OCS\IDiscoveryService::class),
				$this->serverContainer->get(IJobList::class),
				\OC::$server->get(ICloudFederationProviderManager::class),
				\OC::$server->get(ICloudFederationFactory::class),
				$this->serverContainer->get(IEventDispatcher::class),
				$this->serverContainer->get(LoggerInterface::class),
			);
			$tokenHandler = new TokenHandler(
				$this->serverContainer->get(ISecureRandom::class)
			);

			$this->federatedProvider = new FederatedShareProvider(
				$this->serverContainer->get(IDBConnection::class),
				$addressHandler,
				$notifications,
				$tokenHandler,
				$l,
				$this->serverContainer->get(IRootFolder::class),
				$this->serverContainer->get(AllConfig::class),
				$this->serverContainer->get(IUserManager::class),
				$this->serverContainer->get(ICloudIdManager::class),
				$this->serverContainer->get(IConfig::class),
				$this->serverContainer->get(ICloudFederationProviderManager::class),
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
			$appManager = $this->serverContainer->get(IAppManager::class);
			if (!$appManager->isEnabledForUser('sharebymail')) {
				return null;
			}

			$settingsManager = new SettingsManager($this->serverContainer->get(AllConfig::class));

			$this->shareByMailProvider = new ShareByMailProvider(
				$this->serverContainer->get(AllConfig::class),
				$this->serverContainer->get(IDBConnection::class),
				$this->serverContainer->get(ISecureRandom::class),
				$this->serverContainer->get(IUserManager::class),
				$this->serverContainer->get(IRootFolder::class),
				$this->serverContainer->get(IFactory::class)->get('sharebymail'),
				$this->serverContainer->get(LoggerInterface::class),
				$this->serverContainer->get(IMailer::class),
				$this->serverContainer->get(IURLGenerator::class),
				$this->serverContainer->get(IActivityManager::class),
				$settingsManager,
				$this->serverContainer->get(Defaults::class),
				$this->serverContainer->get(IHasher::class),
				$this->serverContainer->get(IEventDispatcher::class),
				$this->serverContainer->get(IShareManager::class)
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

		if (!$this->serverContainer->get(IAppManager::class)->isEnabledForUser('circles') ||
			!class_exists('\OCA\Circles\ShareByCircleProvider')
		) {
			$this->circlesAreNotAvailable = true;
			return null;
		}

		if ($this->shareByCircleProvider === null) {
			$this->shareByCircleProvider = new \OCA\Circles\ShareByCircleProvider(
				$this->serverContainer->get(IDBConnection::class),
				$this->serverContainer->get(ISecureRandom::class),
				$this->serverContainer->get(IUserManager::class),
				$this->serverContainer->get(IRootFolder::class),
				$this->serverContainer->get(IFactory::class)->get('circles'),
				$this->serverContainer->get(LoggerInterface::class),
				$this->serverContainer->get(IURLGenerator::class)
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
			$appManager = $this->serverContainer->get(IAppManager::class);
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
