<?php
/**
 * @copyright Copyright (c) 2017 Roger Szabo <roger.szabo@web.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roger Szabo <roger.szabo@web.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\User_LDAP\AppInfo;

use OCA\Files_External\Service\BackendService;
use OCA\User_LDAP\Controller\RenewPasswordController;
use OCA\User_LDAP\Group_Proxy;
use OCA\User_LDAP\GroupPluginManager;
use OCA\User_LDAP\Handler\ExtStorageConfigHandler;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\Notification\Notifier;
use OCA\User_LDAP\User_Proxy;
use OCA\User_LDAP\UserPluginManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\IAppContainer;
use OCP\IL10N;

class Application extends App implements IBootstrap {
	public function __construct() {
		parent::__construct('user_ldap');
		$container = $this->getContainer();

		/**
		 * Controller
		 */
		$container->registerService('RenewPasswordController', function (IAppContainer $c) {
			/** @var \OC\Server $server */
			$server = $c->query('ServerContainer');

			return new RenewPasswordController(
				$c->getAppName(),
				$server->getRequest(),
				$c->query('UserManager'),
				$server->getConfig(),
				$c->query(IL10N::class),
				$c->query('Session'),
				$server->getURLGenerator()
			);
		});

		$container->registerService(ILDAPWrapper::class, function () {
			return new LDAP();
		});
	}

	public function register(IRegistrationContext $context): void {
	}

	public function boot(IBootContext $context): void {
		$server = $context->getServerContainer();
		$config = $server->getConfig();

		$helper = new Helper($config);
		$configPrefixes = $helper->getServerConfigurationPrefixes(true);
		if (count($configPrefixes) > 0) {
			$ldapWrapper = new LDAP();

			$notificationManager = $server->getNotificationManager();
			$notificationManager->registerNotifierService(Notifier::class);
			$userSession = $server->getUserSession();

			$userPluginManager = $server->query(UserPluginManager::class);
			$groupPluginManager = $server->query(GroupPluginManager::class);

			$userBackend  = new User_Proxy(
				$configPrefixes, $ldapWrapper, $config, $notificationManager, $userSession, $userPluginManager
			);
			$groupBackend  = new Group_Proxy($configPrefixes, $ldapWrapper, $groupPluginManager);
			// register user backend
			\OC_User::useBackend($userBackend);

			// Hook to allow plugins to work on registered backends
			$server->getEventDispatcher()->dispatch('OCA\\User_LDAP\\User\\User::postLDAPBackendAdded');

			$server->getGroupManager()->addBackend($groupBackend);

			$this->registerBackendDependents($context->getAppContainer());
		}

		\OCP\Util::connectHook(
			'\OCA\Files_Sharing\API\Server2Server',
			'preLoginNameUsedAsUserName',
			'\OCA\User_LDAP\Helper',
			'loginName2UserName'
		);
	}

	public function registerBackendDependents(IAppContainer $appContainer) {
		$appContainer->getServer()->getEventDispatcher()->addListener(
			'OCA\\Files_External::loadAdditionalBackends',
			function () use ($appContainer) {
				$storagesBackendService = $appContainer->query(BackendService::class);
				$storagesBackendService->registerConfigHandler('home', function () use ($appContainer) {
					return $appContainer->query(ExtStorageConfigHandler::class);
				});
			}
		);
	}
}
