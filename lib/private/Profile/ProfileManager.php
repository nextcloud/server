<?php

declare(strict_types=1);

/**
 * @copyright 2021 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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

namespace OC\Profile;

use function Safe\array_flip;
use function Safe\usort;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\Core\Db\ProfileConfig;
use OC\Core\Db\ProfileConfigMapper;
use OC\KnownUser\KnownUserService;
use OC\Profile\Actions\EmailAction;
use OC\Profile\Actions\PhoneAction;
use OC\Profile\Actions\TwitterAction;
use OC\Profile\Actions\FediverseAction;
use OC\Profile\Actions\WebsiteAction;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\App\IAppManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IConfig;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Profile\ILinkAction;
use OCP\Cache\CappedMemoryCache;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ProfileManager {
	/** @var IAccountManager */
	private $accountManager;

	/** @var IAppManager */
	private $appManager;

	/** @var IConfig */
	private $config;

	/** @var ProfileConfigMapper */
	private $configMapper;

	/** @var ContainerInterface */
	private $container;

	/** @var KnownUserService */
	private $knownUserService;

	/** @var IFactory */
	private $l10nFactory;

	/** @var LoggerInterface */
	private $logger;

	/** @var Coordinator */
	private $coordinator;

	/** @var ILinkAction[] */
	private $actions = [];

	/** @var null|ILinkAction[] */
	private $sortedActions = null;
	/** @var CappedMemoryCache<ProfileConfig> */
	private CappedMemoryCache $configCache;

	private const CORE_APP_ID = 'core';

	/**
	 * Array of account property actions
	 */
	private const ACCOUNT_PROPERTY_ACTIONS = [
		EmailAction::class,
		PhoneAction::class,
		WebsiteAction::class,
		TwitterAction::class,
		FediverseAction::class,
	];

	/**
	 * Array of account properties displayed on the profile
	 */
	private const PROFILE_PROPERTIES = [
		IAccountManager::PROPERTY_ADDRESS,
		IAccountManager::PROPERTY_AVATAR,
		IAccountManager::PROPERTY_BIOGRAPHY,
		IAccountManager::PROPERTY_DISPLAYNAME,
		IAccountManager::PROPERTY_HEADLINE,
		IAccountManager::PROPERTY_ORGANISATION,
		IAccountManager::PROPERTY_ROLE,
	];

	public function __construct(
		IAccountManager $accountManager,
		IAppManager $appManager,
		IConfig $config,
		ProfileConfigMapper $configMapper,
		ContainerInterface $container,
		KnownUserService $knownUserService,
		IFactory $l10nFactory,
		LoggerInterface $logger,
		Coordinator $coordinator
	) {
		$this->accountManager = $accountManager;
		$this->appManager = $appManager;
		$this->config = $config;
		$this->configMapper = $configMapper;
		$this->container = $container;
		$this->knownUserService = $knownUserService;
		$this->l10nFactory = $l10nFactory;
		$this->logger = $logger;
		$this->coordinator = $coordinator;
		$this->configCache = new CappedMemoryCache();
	}

	/**
	 * If no user is passed as an argument return whether profile is enabled globally in `config.php`
	 */
	public function isProfileEnabled(?IUser $user = null): ?bool {
		$profileEnabledGlobally = $this->config->getSystemValueBool('profile.enabled', true);

		if (empty($user) || !$profileEnabledGlobally) {
			return $profileEnabledGlobally;
		}

		$account = $this->accountManager->getAccount($user);
		return filter_var(
			$account->getProperty(IAccountManager::PROPERTY_PROFILE_ENABLED)->getValue(),
			FILTER_VALIDATE_BOOLEAN,
			FILTER_NULL_ON_FAILURE,
		);
	}

	/**
	 * Register an action for the user
	 */
	private function registerAction(ILinkAction $action, IUser $targetUser, ?IUser $visitingUser): void {
		$action->preload($targetUser);

		if ($action->getTarget() === null) {
			// Actions without a target are not registered
			return;
		}

		if ($action->getAppId() !== self::CORE_APP_ID) {
			if (!$this->appManager->isEnabledForUser($action->getAppId(), $targetUser)) {
				$this->logger->notice('App: ' . $action->getAppId() . ' cannot register actions as it is not enabled for the target user: ' . $targetUser->getUID());
				return;
			}
			if (!$this->appManager->isEnabledForUser($action->getAppId(), $visitingUser)) {
				$this->logger->notice('App: ' . $action->getAppId() . ' cannot register actions as it is not enabled for the visiting user: ' . $visitingUser->getUID());
				return;
			}
		}

		if (in_array($action->getId(), self::PROFILE_PROPERTIES, true)) {
			$this->logger->error('Cannot register action with ID: ' . $action->getId() . ', as it is used by a core account property.');
			return;
		}

		if (isset($this->actions[$action->getId()])) {
			$this->logger->error('Cannot register duplicate action: ' . $action->getId());
			return;
		}

		// Add action to associative array of actions
		$this->actions[$action->getId()] = $action;
	}

	/**
	 * Return an array of registered profile actions for the user
	 *
	 * @return ILinkAction[]
	 */
	private function getActions(IUser $targetUser, ?IUser $visitingUser): array {
		// If actions are already registered and sorted, return them
		if ($this->sortedActions !== null) {
			return $this->sortedActions;
		}

		foreach (self::ACCOUNT_PROPERTY_ACTIONS as $actionClass) {
			/** @var ILinkAction $action */
			$action = $this->container->get($actionClass);
			$this->registerAction($action, $targetUser, $visitingUser);
		}

		$context = $this->coordinator->getRegistrationContext();

		if ($context !== null) {
			foreach ($context->getProfileLinkActions() as $registration) {
				/** @var ILinkAction $action */
				$action = $this->container->get($registration->getService());
				$this->registerAction($action, $targetUser, $visitingUser);
			}
		}

		$actionsClone = $this->actions;
		// Sort associative array into indexed array in ascending order of priority
		usort($actionsClone, function (ILinkAction $a, ILinkAction $b) {
			return $a->getPriority() === $b->getPriority() ? 0 : ($a->getPriority() < $b->getPriority() ? -1 : 1);
		});

		$this->sortedActions = $actionsClone;
		return $this->sortedActions;
	}

	/**
	 * Return whether the profile parameter of the target user
	 * is visible to the visiting user
	 */
	private function isParameterVisible(string $paramId, IUser $targetUser, ?IUser $visitingUser): bool {
		try {
			$account = $this->accountManager->getAccount($targetUser);
			$scope = $account->getProperty($paramId)->getScope();
		} catch (PropertyDoesNotExistException $e) {
			// Allow the exception as not all profile parameters are account properties
		}

		$visibility = $this->getProfileConfig($targetUser, $visitingUser)[$paramId]['visibility'];
		// Handle profile visibility and account property scope
		switch ($visibility) {
			case ProfileConfig::VISIBILITY_HIDE:
				return false;
			case ProfileConfig::VISIBILITY_SHOW_USERS_ONLY:
				if (!empty($scope)) {
					switch ($scope) {
						case IAccountManager::SCOPE_PRIVATE:
							return $visitingUser !== null && $this->knownUserService->isKnownToUser($targetUser->getUID(), $visitingUser->getUID());
						case IAccountManager::SCOPE_LOCAL:
						case IAccountManager::SCOPE_FEDERATED:
						case IAccountManager::SCOPE_PUBLISHED:
							return $visitingUser !== null;
						default:
							return false;
					}
				}
				return $visitingUser !== null;
			case ProfileConfig::VISIBILITY_SHOW:
				if (!empty($scope)) {
					switch ($scope) {
						case IAccountManager::SCOPE_PRIVATE:
							return $visitingUser !== null && $this->knownUserService->isKnownToUser($targetUser->getUID(), $visitingUser->getUID());
						case IAccountManager::SCOPE_LOCAL:
						case IAccountManager::SCOPE_FEDERATED:
						case IAccountManager::SCOPE_PUBLISHED:
							return true;
						default:
							return false;
					}
				}
				return true;
			default:
				return false;
		}
	}

	/**
	 * Return the profile parameters of the target user that are visible to the visiting user
	 * in an associative array
	 */
	public function getProfileParams(IUser $targetUser, ?IUser $visitingUser): array {
		$account = $this->accountManager->getAccount($targetUser);

		// Initialize associative array of profile parameters
		$profileParameters = [
			'userId' => $account->getUser()->getUID(),
		];

		// Add account properties
		foreach (self::PROFILE_PROPERTIES as $property) {
			switch ($property) {
				case IAccountManager::PROPERTY_ADDRESS:
				case IAccountManager::PROPERTY_BIOGRAPHY:
				case IAccountManager::PROPERTY_DISPLAYNAME:
				case IAccountManager::PROPERTY_HEADLINE:
				case IAccountManager::PROPERTY_ORGANISATION:
				case IAccountManager::PROPERTY_ROLE:
					$profileParameters[$property] =
						$this->isParameterVisible($property, $targetUser, $visitingUser)
						// Explicitly set to null when value is empty string
						? ($account->getProperty($property)->getValue() ?: null)
						: null;
					break;
				case IAccountManager::PROPERTY_AVATAR:
					// Add avatar visibility
					$profileParameters['isUserAvatarVisible'] = $this->isParameterVisible($property, $targetUser, $visitingUser);
					break;
			}
		}

		// Add actions
		$profileParameters['actions'] = array_map(
			function (ILinkAction $action) {
				return [
					'id' => $action->getId(),
					'icon' => $action->getIcon(),
					'title' => $action->getTitle(),
					'target' => $action->getTarget(),
				];
			},
			// This is needed to reindex the array after filtering
			array_values(
				array_filter(
					$this->getActions($targetUser, $visitingUser),
					function (ILinkAction $action) use ($targetUser, $visitingUser) {
						return $this->isParameterVisible($action->getId(), $targetUser, $visitingUser);
					}
				),
			)
		);

		return $profileParameters;
	}

	/**
	 * Return the filtered profile config containing only
	 * the properties to be stored on the database
	 */
	private function filterNotStoredProfileConfig(array $profileConfig): array {
		$dbParamConfigProperties = [
			'visibility',
		];

		foreach ($profileConfig as $paramId => $paramConfig) {
			$profileConfig[$paramId] = array_intersect_key($paramConfig, array_flip($dbParamConfigProperties));
		}

		return $profileConfig;
	}

	/**
	 * Return the default profile config
	 */
	private function getDefaultProfileConfig(IUser $targetUser, ?IUser $visitingUser): array {
		// Construct the default config for actions
		$actionsConfig = [];
		foreach ($this->getActions($targetUser, $visitingUser) as $action) {
			$actionsConfig[$action->getId()] = ['visibility' => ProfileConfig::DEFAULT_VISIBILITY];
		}

		// Construct the default config for account properties
		$propertiesConfig = [];
		foreach (ProfileConfig::DEFAULT_PROPERTY_VISIBILITY as $property => $visibility) {
			$propertiesConfig[$property] = ['visibility' => $visibility];
		}

		return array_merge($actionsConfig, $propertiesConfig);
	}

	/**
	 * Return the profile config of the target user,
	 * if a config does not already exist a default config is created and returned
	 */
	public function getProfileConfig(IUser $targetUser, ?IUser $visitingUser): array {
		$defaultProfileConfig = $this->getDefaultProfileConfig($targetUser, $visitingUser);
		try {
			if (($config = $this->configCache[$targetUser->getUID()]) === null) {
				$config = $this->configMapper->get($targetUser->getUID());
				$this->configCache[$targetUser->getUID()] = $config;
			}
			// Merge defaults with the existing config in case the defaults are missing
			$config->setConfigArray(array_merge(
				$defaultProfileConfig,
				$this->filterNotStoredProfileConfig($config->getConfigArray()),
			));
			$this->configMapper->update($config);
			$configArray = $config->getConfigArray();
		} catch (DoesNotExistException $e) {
			// Create a new default config if it does not exist
			$config = new ProfileConfig();
			$config->setUserId($targetUser->getUID());
			$config->setConfigArray($defaultProfileConfig);
			$this->configMapper->insert($config);
			$configArray = $config->getConfigArray();
		}

		return $configArray;
	}

	/**
	 * Return the profile config of the target user with additional medatata,
	 * if a config does not already exist a default config is created and returned
	 */
	public function getProfileConfigWithMetadata(IUser $targetUser, ?IUser $visitingUser): array {
		$configArray = $this->getProfileConfig($targetUser, $visitingUser);

		$actionsMetadata = [];
		foreach ($this->getActions($targetUser, $visitingUser) as $action) {
			$actionsMetadata[$action->getId()] = [
				'appId' => $action->getAppId(),
				'displayId' => $action->getDisplayId(),
			];
		}

		// Add metadata for account property actions which are always configurable
		foreach (self::ACCOUNT_PROPERTY_ACTIONS as $actionClass) {
			/** @var ILinkAction $action */
			$action = $this->container->get($actionClass);
			if (!isset($actionsMetadata[$action->getId()])) {
				$actionsMetadata[$action->getId()] = [
					'appId' => $action->getAppId(),
					'displayId' => $action->getDisplayId(),
				];
			}
		}

		$propertiesMetadata = [
			IAccountManager::PROPERTY_ADDRESS => [
				'appId' => self::CORE_APP_ID,
				'displayId' => $this->l10nFactory->get('lib')->t('Address'),
			],
			IAccountManager::PROPERTY_AVATAR => [
				'appId' => self::CORE_APP_ID,
				'displayId' => $this->l10nFactory->get('lib')->t('Profile picture'),
			],
			IAccountManager::PROPERTY_BIOGRAPHY => [
				'appId' => self::CORE_APP_ID,
				'displayId' => $this->l10nFactory->get('lib')->t('About'),
			],
			IAccountManager::PROPERTY_DISPLAYNAME => [
				'appId' => self::CORE_APP_ID,
				'displayId' => $this->l10nFactory->get('lib')->t('Full name'),
			],
			IAccountManager::PROPERTY_HEADLINE => [
				'appId' => self::CORE_APP_ID,
				'displayId' => $this->l10nFactory->get('lib')->t('Headline'),
			],
			IAccountManager::PROPERTY_ORGANISATION => [
				'appId' => self::CORE_APP_ID,
				'displayId' => $this->l10nFactory->get('lib')->t('Organisation'),
			],
			IAccountManager::PROPERTY_ROLE => [
				'appId' => self::CORE_APP_ID,
				'displayId' => $this->l10nFactory->get('lib')->t('Role'),
			],
		];

		$paramMetadata = array_merge($actionsMetadata, $propertiesMetadata);
		$configArray = array_intersect_key($configArray, $paramMetadata);

		foreach ($configArray as $paramId => $paramConfig) {
			if (isset($paramMetadata[$paramId])) {
				$configArray[$paramId] = array_merge(
					$paramConfig,
					$paramMetadata[$paramId],
				);
			}
		}

		return $configArray;
	}
}
