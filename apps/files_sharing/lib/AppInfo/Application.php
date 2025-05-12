<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\AppInfo;

use OC\Group\DisplayNameCache as GroupDisplayNameCache;
use OC\Share\Share;
use OC\User\DisplayNameCache;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files\Event\LoadSidebar;
use OCA\Files_Sharing\Capabilities;
use OCA\Files_Sharing\Config\ConfigLexicon;
use OCA\Files_Sharing\External\Manager;
use OCA\Files_Sharing\External\MountProvider as ExternalMountProvider;
use OCA\Files_Sharing\Helper;
use OCA\Files_Sharing\Listener\BeforeDirectFileDownloadListener;
use OCA\Files_Sharing\Listener\BeforeNodeReadListener;
use OCA\Files_Sharing\Listener\BeforeZipCreatedListener;
use OCA\Files_Sharing\Listener\LoadAdditionalListener;
use OCA\Files_Sharing\Listener\LoadPublicFileRequestAuthListener;
use OCA\Files_Sharing\Listener\LoadSidebarListener;
use OCA\Files_Sharing\Listener\ShareInteractionListener;
use OCA\Files_Sharing\Listener\UserAddedToGroupListener;
use OCA\Files_Sharing\Listener\UserShareAcceptanceListener;
use OCA\Files_Sharing\Middleware\OCSShareAPIMiddleware;
use OCA\Files_Sharing\Middleware\ShareInfoMiddleware;
use OCA\Files_Sharing\Middleware\SharingCheckMiddleware;
use OCA\Files_Sharing\MountProvider;
use OCA\Files_Sharing\Notification\Listener;
use OCA\Files_Sharing\Notification\Notifier;
use OCA\Files_Sharing\ShareBackend\File;
use OCA\Files_Sharing\ShareBackend\Folder;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\Collaboration\Resources\LoadAdditionalScriptsEvent as ResourcesLoadAdditionalScriptsEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudIdManager;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Events\BeforeDirectFileDownloadEvent;
use OCP\Files\Events\BeforeZipCreatedEvent;
use OCP\Files\Events\Node\BeforeNodeReadEvent;
use OCP\Group\Events\GroupChangedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\Util;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\GenericEvent as OldGenericEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'files_sharing';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerService(ExternalMountProvider::class, function (ContainerInterface $c) {
			return new ExternalMountProvider(
				$c->get(IDBConnection::class),
				function () use ($c) {
					return $c->get(Manager::class);
				},
				$c->get(ICloudIdManager::class)
			);
		});

		/**
		 * Middleware
		 */
		$context->registerMiddleWare(SharingCheckMiddleware::class);
		$context->registerMiddleWare(OCSShareAPIMiddleware::class);
		$context->registerMiddleWare(ShareInfoMiddleware::class);

		$context->registerCapability(Capabilities::class);

		$context->registerNotifierService(Notifier::class);
		$context->registerEventListener(UserChangedEvent::class, DisplayNameCache::class);
		$context->registerEventListener(UserDeletedEvent::class, DisplayNameCache::class);
		$context->registerEventListener(GroupChangedEvent::class, GroupDisplayNameCache::class);
		$context->registerEventListener(GroupDeletedEvent::class, GroupDisplayNameCache::class);

		// Sidebar and files scripts
		$context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadAdditionalListener::class);
		$context->registerEventListener(LoadSidebar::class, LoadSidebarListener::class);
		$context->registerEventListener(ShareCreatedEvent::class, ShareInteractionListener::class);
		$context->registerEventListener(ShareCreatedEvent::class, UserShareAcceptanceListener::class);
		$context->registerEventListener(UserAddedEvent::class, UserAddedToGroupListener::class);

		// Publish activity for public download
		$context->registerEventListener(BeforeNodeReadEvent::class, BeforeNodeReadListener::class);
		$context->registerEventListener(BeforeZipCreatedEvent::class, BeforeNodeReadListener::class);

		// Handle download events for view only checks. Priority higher than 0 to run early.
		$context->registerEventListener(BeforeZipCreatedEvent::class, BeforeZipCreatedListener::class, 5);
		$context->registerEventListener(BeforeDirectFileDownloadEvent::class, BeforeDirectFileDownloadListener::class, 5);

		// File request auth
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, LoadPublicFileRequestAuthListener::class);

		$context->registerConfigLexicon(ConfigLexicon::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn([$this, 'registerMountProviders']);
		$context->injectFn([$this, 'registerEventsScripts']);

		Helper::registerHooks();

		Share::registerBackend('file', File::class);
		Share::registerBackend('folder', Folder::class, 'file');
	}


	public function registerMountProviders(IMountProviderCollection $mountProviderCollection, MountProvider $mountProvider, ExternalMountProvider $externalMountProvider): void {
		$mountProviderCollection->registerProvider($mountProvider);
		$mountProviderCollection->registerProvider($externalMountProvider);
	}

	public function registerEventsScripts(IEventDispatcher $dispatcher): void {
		$dispatcher->addListener(ResourcesLoadAdditionalScriptsEvent::class, function (): void {
			Util::addScript('files_sharing', 'collaboration');
		});
		$dispatcher->addListener(BeforeTemplateRenderedEvent::class, function (): void {
			/**
			 * Always add main sharing script
			 */
			Util::addScript(self::APP_ID, 'main');
		});

		// notifications api to accept incoming user shares
		$dispatcher->addListener(ShareCreatedEvent::class, function (ShareCreatedEvent $event): void {
			/** @var Listener $listener */
			$listener = $this->getContainer()->query(Listener::class);
			$listener->shareNotification($event);
		});
		$dispatcher->addListener(IGroup::class . '::postAddUser', function ($event): void {
			if (!$event instanceof OldGenericEvent) {
				return;
			}
			/** @var Listener $listener */
			$listener = $this->getContainer()->query(Listener::class);
			$listener->userAddedToGroup($event);
		});
	}
}
