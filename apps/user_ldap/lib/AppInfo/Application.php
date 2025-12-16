<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\AppInfo;

use Closure;
use OCA\Files_External\Service\BackendService;
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
use OCP\AppFramework\Services\IAppConfig;
use OCP\Config\IUserConfig;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\Image;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;
use OCP\Share\IManager as IShareManager;
use OCP\User\Events\PostLoginEvent;
use OCP\Util;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap {
	public const APP_ID = 'user_ldap';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerService(ILDAPWrapper::class, function (ContainerInterface $c) {
			return new LDAP(
				$c->get(IConfig::class)->getSystemValueString('ldap_log_file')
			);
		});

		$context->registerNotifierService(Notifier::class);

		$context->registerService(
			Manager::class,
			function (ContainerInterface $c) {
				return new Manager(
					$c->get(IConfig::class),
					$c->get(IUserConfig::class),
					$c->get(IAppConfig::class),
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
			IUserManager $userManager,
			IGroupManager $groupManager,
			User_Proxy $userBackend,
			Group_Proxy $groupBackend,
			Helper $helper,
		): void {
			$configPrefixes = $helper->getServerConfigurationPrefixes(true);
			if (count($configPrefixes) > 0) {
				$userPluginManager = $appContainer->get(UserPluginManager::class);
				$groupPluginManager = $appContainer->get(GroupPluginManager::class);

				$userManager->registerBackend($userBackend);
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
