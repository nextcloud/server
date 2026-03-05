<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Versions\AppInfo;

use OC\KnownUser\KnownUserService;
use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files\Event\LoadSidebar;
use OCA\Files_Versions\Capabilities;
use OCA\Files_Versions\Events\VersionRestoredEvent;
use OCA\Files_Versions\Listener\FileEventsListener;
use OCA\Files_Versions\Listener\LegacyRollbackListener;
use OCA\Files_Versions\Listener\LoadAdditionalListener;
use OCA\Files_Versions\Listener\LoadSidebarListener;
use OCA\Files_Versions\Listener\VersionAuthorListener;
use OCA\Files_Versions\Listener\VersionStorageMoveListener;
use OCA\Files_Versions\Versions\IVersionManager;
use OCA\Files_Versions\Versions\VersionManager;
use OCP\Accounts\IAccountManager;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Events\Node\BeforeNodeCopiedEvent;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\BeforeNodeRenamedEvent;
use OCP\Files\Events\Node\BeforeNodeTouchedEvent;
use OCP\Files\Events\Node\BeforeNodeWrittenEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Events\Node\NodeTouchedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IServerContainer;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Share\IManager as IShareManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap {
	public const APP_ID = 'files_versions';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		/**
		 * Register capabilities
		 */
		$context->registerCapability(Capabilities::class);

		/**
		 * Register $principalBackend for the DAV collection
		 */
		$context->registerService('principalBackend', function (ContainerInterface $c) {
			/** @var IServerContainer $server */
			$server = $c->get(IServerContainer::class);
			return new Principal(
				$server->get(IUserManager::class),
				$server->get(IGroupManager::class),
				Server::get(IAccountManager::class),
				$server->get(IShareManager::class),
				$server->get(IUserSession::class),
				$server->get(IAppManager::class),
				$server->get(ProxyMapper::class),
				$server->get(KnownUserService::class),
				$server->get(IConfig::class),
				$server->get(IFactory::class),
			);
		});

		$context->registerServiceAlias(IVersionManager::class, VersionManager::class);

		/**
		 * Register Events
		 */
		$context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadAdditionalListener::class);
		$context->registerEventListener(LoadSidebar::class, LoadSidebarListener::class);

		$context->registerEventListener(BeforeNodeRenamedEvent::class, VersionStorageMoveListener::class);
		$context->registerEventListener(NodeRenamedEvent::class, VersionStorageMoveListener::class);
		$context->registerEventListener(BeforeNodeCopiedEvent::class, VersionStorageMoveListener::class);
		$context->registerEventListener(NodeCopiedEvent::class, VersionStorageMoveListener::class);

		$context->registerEventListener(NodeCreatedEvent::class, FileEventsListener::class);
		$context->registerEventListener(BeforeNodeTouchedEvent::class, FileEventsListener::class);
		$context->registerEventListener(NodeTouchedEvent::class, FileEventsListener::class);
		$context->registerEventListener(BeforeNodeWrittenEvent::class, FileEventsListener::class);
		$context->registerEventListener(NodeWrittenEvent::class, FileEventsListener::class);
		$context->registerEventListener(BeforeNodeDeletedEvent::class, FileEventsListener::class);
		$context->registerEventListener(NodeDeletedEvent::class, FileEventsListener::class);
		$context->registerEventListener(NodeRenamedEvent::class, FileEventsListener::class);
		$context->registerEventListener(NodeCopiedEvent::class, FileEventsListener::class);
		$context->registerEventListener(BeforeNodeRenamedEvent::class, FileEventsListener::class);
		$context->registerEventListener(BeforeNodeCopiedEvent::class, FileEventsListener::class);

		// we add the version author listener with lower priority to make sure new versions already are created by FileEventsListener
		$context->registerEventListener(NodeWrittenEvent::class, VersionAuthorListener::class, -1);

		$context->registerEventListener(VersionRestoredEvent::class, LegacyRollbackListener::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(\Closure::fromCallable([$this, 'registerVersionBackends']));
	}

	public function registerVersionBackends(ContainerInterface $container, IAppManager $appManager, LoggerInterface $logger): void {
		foreach ($appManager->getEnabledApps() as $app) {
			$appInfo = $appManager->getAppInfo($app);
			if (isset($appInfo['versions'])) {
				$backends = $appInfo['versions'];
				foreach ($backends as $backend) {
					if (isset($backend['@value'])) {
						$this->loadBackend($backend, $container, $logger);
					} else {
						foreach ($backend as $singleBackend) {
							$this->loadBackend($singleBackend, $container, $logger);
						}
					}
				}
			}
		}
	}

	private function loadBackend(array $backend, ContainerInterface $container, LoggerInterface $logger): void {
		/** @var IVersionManager $versionManager */
		$versionManager = $container->get(IVersionManager::class);
		$class = $backend['@value'];
		$for = $backend['@attributes']['for'];
		try {
			$backendObject = $container->get($class);
			$versionManager->registerBackend($for, $backendObject);
		} catch (\Exception $e) {
			$logger->error($e->getMessage(), ['exception' => $e]);
		}
	}
}
