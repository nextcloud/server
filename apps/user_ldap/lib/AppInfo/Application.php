<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\AppInfo;

use Closure;
use OCA\Files_External\Service\BackendService;
use OCA\User_LDAP\Controller\RenewPasswordController;
use OCA\User_LDAP\Events\GroupBackendRegistered;
use OCA\User_LDAP\Events\UserBackendRegistered;
use OCA\User_LDAP\Group_Proxy;
use OCA\User_LDAP\GroupPluginManager;
use OCA\User_LDAP\Handler\ExtStorageConfigHandler;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\LoginListener;
use OCA\User_LDAP\Notification\Notifier;
use OCA\User_LDAP\SetupChecks\LdapConnection;
use OCA\User_LDAP\SetupChecks\LdapInvalidUuids;
use OCA\User_LDAP\User\Manager;
use OCA\User_LDAP\User_Proxy;
use OCA\User_LDAP\UserPluginManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\IAppContainer;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\Image;
use OCP\IServerContainer;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;
use OCP\Share\IManager as IShareManager;
use OCP\User\Events\PostLoginEvent;
use OCP\Util;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap {
	public function __construct() {
		parent::__construct('user_ldap');
		$container = $this->getContainer();

		/**
		 * Controller
		 */
		$container->registerService('RenewPasswordController', function (IAppContainer $appContainer) {
			/** @var IServerContainer $server */
			$server = $appContainer->get(IServerContainer::class);

			return new RenewPasswordController(
				$appContainer->get('AppName'),
				$server->getRequest(),
				$appContainer->get('UserManager'),
				$server->getConfig(),
				$appContainer->get(IL10N::class),
				$appContainer->get('Session'),
				$server->getURLGenerator()
			);
		});

		$container->registerService(ILDAPWrapper::class, function (IAppContainer $appContainer) {
			/** @var IServerContainer $server */
			$server = $appContainer->get(IServerContainer::class);

			return new LDAP(
				$server->getConfig()->getSystemValueString('ldap_log_file')
			);
		});
	}

	public function register(IRegistrationContext $context): void {
		$context->registerNotifierService(Notifier::class);

		$context->registerService(
			Manager::class,
			function (ContainerInterface $c) {
				return new Manager(
					$c->get(IConfig::class),
					$c->get(LoggerInterface::class),
					$c->get(IAvatarManager::class),
					$c->get(Image::class),
					$c->get(IUserManager::class),
					$c->get(INotificationManager::class),
					$c->get(IShareManager::class),
				);
			},
			// the instance is specific to a lazy bound Access instance, thus cannot be shared.
			false
		);
		$context->registerEventListener(PostLoginEvent::class, LoginListener::class);
		$context->registerSetupCheck(LdapInvalidUuids::class);
		$context->registerSetupCheck(LdapConnection::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(function (
			INotificationManager $notificationManager,
			IAppContainer $appContainer,
			IEventDispatcher $dispatcher,
			IGroupManager $groupManager,
			User_Proxy $userBackend,
			Group_Proxy $groupBackend,
			Helper $helper,
		): void {
			$configPrefixes = $helper->getServerConfigurationPrefixes(true);
			if (count($configPrefixes) > 0) {
				$userPluginManager = $appContainer->get(UserPluginManager::class);
				$groupPluginManager = $appContainer->get(GroupPluginManager::class);

				\OC_User::useBackend($userBackend);
				$groupManager->addBackend($groupBackend);

				$userBackendRegisteredEvent = new UserBackendRegistered($userBackend, $userPluginManager);
				$dispatcher->dispatch('OCA\\User_LDAP\\User\\User::postLDAPBackendAdded', $userBackendRegisteredEvent);
				$dispatcher->dispatchTyped($userBackendRegisteredEvent);
				$groupBackendRegisteredEvent = new GroupBackendRegistered($groupBackend, $groupPluginManager);
				$dispatcher->dispatchTyped($groupBackendRegisteredEvent);
			}
		});

		$context->injectFn(Closure::fromCallable([$this, 'registerBackendDependents']));

		Util::connectHook(
			'\OCA\Files_Sharing\API\Server2Server',
			'preLoginNameUsedAsUserName',
			'\OCA\User_LDAP\Helper',
			'loginName2UserName'
		);
	}

	private function registerBackendDependents(IAppContainer $appContainer, IEventDispatcher $dispatcher): void {
		$dispatcher->addListener(
			'OCA\\Files_External::loadAdditionalBackends',
			function () use ($appContainer): void {
				$storagesBackendService = $appContainer->get(BackendService::class);
				$storagesBackendService->registerConfigHandler('home', function () use ($appContainer) {
					return $appContainer->get(ExtStorageConfigHandler::class);
				});
			}
		);
	}
}
