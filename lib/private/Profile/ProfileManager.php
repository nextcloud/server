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

use function Safe\usort;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\Core\Db\ProfileConfig;
use OC\Core\Db\ProfileConfigMapper;
use OC\KnownUser\KnownUserService;
use OC\Profile\Actions\EmailAction;
use OC\Profile\Actions\PhoneAction;
use OC\Profile\Actions\TwitterAction;
use OC\Profile\Actions\WebsiteAction;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\App\IAppManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Profile\ILinkAction;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ProfileManager {

	/** @var IAccountManager */
	private $accountManager;

	/** @var IAppManager */
	private $appManager;

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

	/**
	 * Array of account property actions
	 */
	private const ACCOUNT_PROPERTY_ACTIONS = [
		EmailAction::class,
		PhoneAction::class,
		WebsiteAction::class,
		TwitterAction::class,
	];

	/**
	 * Array of account properties displayed on the profile
	 */
	private const PROFILE_PROPERTIES = [
		IAccountManager::PROPERTY_ADDRESS,
		IAccountManager::PROPERTY_BIOGRAPHY,
		IAccountManager::PROPERTY_DISPLAYNAME,
		IAccountManager::PROPERTY_HEADLINE,
		IAccountManager::PROPERTY_ORGANISATION,
		IAccountManager::PROPERTY_ROLE,
	];

	public function __construct(
		IAccountManager $accountManager,
		IAppManager $appManager,
		ProfileConfigMapper $configMapper,
		ContainerInterface $container,
		KnownUserService $knownUserService,
		IFactory $l10nFactory,
		LoggerInterface $logger,
		Coordinator $coordinator
	) {
		$this->accountManager = $accountManager;
		$this->appManager = $appManager;
		$this->configMapper = $configMapper;
		$this->container = $container;
		$this->knownUserService = $knownUserService;
		$this->l10nFactory = $l10nFactory;
		$this->logger = $logger;
		$this->coordinator = $coordinator;
	}

	/**
	 * Register an action for the user
	 */
	private function registerAction(IUser $targetUser, ?IUser $visitingUser, ILinkAction $action): void {
		$action->preload($targetUser);

		if ($action->getTarget() === null) {
			// Actions without a target are not registered
			return;
		}

		if (isset($this->actions[$action->getId()])) {
			$this->logger->error('Cannot register duplicate action: ' . $action->getId());
			return;
		}

		if ($action->getAppId() !== 'core') {
			if (!$this->appManager->isEnabledForUser($action->getAppId(), $targetUser)) {
				$this->logger->notice('App: ' . $action->getAppId() . ' cannot register actions as it is not enabled for the user: ' . $targetUser->getUID());
				return;
			}
			if ($visitingUser === null) {
				$this->logger->notice('App: ' . $action->getAppId() . ' cannot register actions as it is not available to non logged in users');
				return;
			}
			if (!$this->appManager->isEnabledForUser($action->getAppId(), $visitingUser)) {
				$this->logger->notice('App: ' . $action->getAppId() . ' cannot register actions as it is not enabled for the visiting user: ' . $visitingUser->getUID());
				return;
			}
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
		$context = $this->coordinator->getRegistrationContext();
		if ($context === null) {
			return [];
		}

		foreach (self::ACCOUNT_PROPERTY_ACTIONS as $actionClass) {
			/** @var ILinkAction $provider */
			$provider = $this->container->get($actionClass);
			$this->registerAction($targetUser, $visitingUser, $provider);
		}

		foreach ($context->getProfileActions() as $registration) {
			/** @var ILinkAction $provider */
			$provider = $this->container->get($registration->getService());
			$this->registerAction($targetUser, $visitingUser, $provider);
		}

		$actionsClone = $this->actions;
		// Sort associative array into indexed array in ascending order of priority
		usort($actionsClone, function (ILinkAction $a, ILinkAction $b) {
			return $a->getPriority() === $b->getPriority() ? 0 : ($a->getPriority() < $b->getPriority() ? -1 : 1);
		});
		return $actionsClone;
	}

	/**
	 * Return whether the profile parameter is visible to the visiting user
	 */
	private function isParameterVisible(IUser $targetUser, ?IUser $visitingUser, string $paramId): bool {
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
	 * Return the profile parameters
	 */
	public function getProfileParams(IUser $targetUser, ?IUser $visitingUser): array {
		$account = $this->accountManager->getAccount($targetUser);
		// Initialize associative array of profile parameters
		$profileParameters = [
			'userId' => $account->getUser()->getUID(),
		];

		// Add account properties
		foreach (self::PROFILE_PROPERTIES as $property) {
			$profileParameters[$property] =
				$this->isParameterVisible($targetUser, $visitingUser, $property)
				// Explicitly set to null when value is empty string
				? ($account->getProperty($property)->getValue() ?: null)
				: null;
		}

		// Add avatar visibility
		$profileParameters['isUserAvatarVisible'] = $this->isParameterVisible($targetUser, $visitingUser, IAccountManager::PROPERTY_AVATAR);

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
						return $this->isParameterVisible($targetUser, $visitingUser, $action->getId());
					}
				),
			)
		);

		return $profileParameters;
	}

	/**
	 * Return the default profile config
	 */
	private function getDefaultProfileConfig(IUser $targetUser, ?IUser $visitingUser): array {
		// Contruct the default config for actions
		$actionsConfig = [];
		foreach ($this->getActions($targetUser, $visitingUser) as $action) {
			$actionsConfig[$action->getId()] = [
				'displayId' => $action->getDisplayId(),
				'visibility' => ProfileConfig::DEFAULT_VISIBILITY,
			];
		}

		// Map of account properties to display IDs
		$propertyDisplayMap = [
			IAccountManager::PROPERTY_ADDRESS => $this->l10nFactory->get('core')->t('Address'),
			IAccountManager::PROPERTY_AVATAR => $this->l10nFactory->get('core')->t('Avatar'),
			IAccountManager::PROPERTY_BIOGRAPHY => $this->l10nFactory->get('core')->t('About'),
			IAccountManager::PROPERTY_DISPLAYNAME => $this->l10nFactory->get('core')->t('Full name'),
			IAccountManager::PROPERTY_HEADLINE => $this->l10nFactory->get('core')->t('Headline'),
			IAccountManager::PROPERTY_ORGANISATION => $this->l10nFactory->get('core')->t('Organisation'),
			IAccountManager::PROPERTY_ROLE => $this->l10nFactory->get('core')->t('Role'),
			IAccountManager::PROPERTY_EMAIL => $this->l10nFactory->get('core')->t('Email'),
			IAccountManager::PROPERTY_PHONE => $this->l10nFactory->get('core')->t('Phone'),
			IAccountManager::PROPERTY_TWITTER => $this->l10nFactory->get('core')->t('Twitter'),
			IAccountManager::PROPERTY_WEBSITE => $this->l10nFactory->get('core')->t('Website'),
		];

		// Contruct the default config for account properties
		$propertiesConfig = [];
		foreach ($propertyDisplayMap as $property => $displayId) {
			$propertiesConfig[$property] = [
				'displayId' => $displayId,
				'visibility' => ProfileConfig::DEFAULT_PROPERTY_VISIBILITY[$property],
			];
		}

		return array_merge($actionsConfig, $propertiesConfig);
	}

	/**
	 * Return the profile config
	 */
	public function getProfileConfig(IUser $targetUser, ?IUser $visitingUser): array {
		$defaultProfileConfig = $this->getDefaultProfileConfig($targetUser, $visitingUser);
		try {
			$config = $this->configMapper->get($targetUser->getUID());
			// Merge defaults with the existing config in case the defaults are missing
			$config->setConfigArray(array_merge($defaultProfileConfig, $config->getConfigArray()));
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
}
