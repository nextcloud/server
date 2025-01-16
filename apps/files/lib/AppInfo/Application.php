<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\AppInfo;

use Closure;
use OCA\Files\Capabilities;
use OCA\Files\Collaboration\Resources\Listener;
use OCA\Files\Collaboration\Resources\ResourceProvider;
use OCA\Files\Controller\ApiController;
use OCA\Files\Dashboard\FavoriteWidget;
use OCA\Files\DirectEditingCapabilities;
use OCA\Files\Event\LoadSearchPlugins;
use OCA\Files\Event\LoadSidebar;
use OCA\Files\Listener\LoadSearchPluginsListener;
use OCA\Files\Listener\LoadSidebarListener;
use OCA\Files\Listener\NodeAddedToFavoriteListener;
use OCA\Files\Listener\NodeRemovedFromFavoriteListener;
use OCA\Files\Listener\RenderReferenceEventListener;
use OCA\Files\Listener\SyncLivePhotosListener;
use OCA\Files\Notification\Notifier;
use OCA\Files\Search\FilesSearchProvider;
use OCA\Files\Service\TagService;
use OCA\Files\Service\UserConfig;
use OCA\Files\Service\ViewConfig;
use OCA\Files\Settings\DeclarativeAdminSettings;
use OCP\Activity\IManager as IActivityManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\Collaboration\Resources\IProviderManager;
use OCP\Files\Cache\CacheEntryRemovedEvent;
use OCP\Files\Events\Node\BeforeNodeCopiedEvent;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\BeforeNodeRenamedEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\NodeAddedToFavorite;
use OCP\Files\Events\NodeRemovedFromFavorite;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IServerContainer;
use OCP\ITagManager;
use OCP\IUserSession;
use OCP\Share\IManager as IShareManager;
use OCP\Util;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap {
	public const APP_ID = 'files';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		/**
		 * Controllers
		 */
		$context->registerService('APIController', function (ContainerInterface $c) {
			/** @var IServerContainer $server */
			$server = $c->get(IServerContainer::class);

			return new ApiController(
				$c->get('AppName'),
				$c->get(IRequest::class),
				$c->get(IUserSession::class),
				$c->get(TagService::class),
				$c->get(IPreview::class),
				$c->get(IShareManager::class),
				$c->get(IConfig::class),
				$server->getUserFolder(),
				$c->get(UserConfig::class),
				$c->get(ViewConfig::class),
				$c->get(IL10N::class),
				$c->get(IRootFolder::class),
				$c->get(LoggerInterface::class),
			);
		});

		/**
		 * Services
		 */
		$context->registerService(TagService::class, function (ContainerInterface $c) {
			/** @var IServerContainer $server */
			$server = $c->get(IServerContainer::class);

			return new TagService(
				$c->get(IUserSession::class),
				$c->get(IActivityManager::class),
				$c->get(ITagManager::class)->load(self::APP_ID),
				$server->getUserFolder(),
			);
		});

		/*
		 * Register capabilities
		 */
		$context->registerCapability(Capabilities::class);
		$context->registerCapability(DirectEditingCapabilities::class);

		$context->registerDeclarativeSettings(DeclarativeAdminSettings::class);

		$context->registerEventListener(LoadSidebar::class, LoadSidebarListener::class);
		$context->registerEventListener(RenderReferenceEvent::class, RenderReferenceEventListener::class);
		$context->registerEventListener(BeforeNodeRenamedEvent::class, SyncLivePhotosListener::class);
		$context->registerEventListener(BeforeNodeDeletedEvent::class, SyncLivePhotosListener::class);
		$context->registerEventListener(CacheEntryRemovedEvent::class, SyncLivePhotosListener::class);
		$context->registerEventListener(BeforeNodeCopiedEvent::class, SyncLivePhotosListener::class);
		$context->registerEventListener(NodeCopiedEvent::class, SyncLivePhotosListener::class);
		$context->registerEventListener(LoadSearchPlugins::class, LoadSearchPluginsListener::class);
		$context->registerEventListener(NodeAddedToFavorite::class, NodeAddedToFavoriteListener::class);
		$context->registerEventListener(NodeRemovedFromFavorite::class, NodeRemovedFromFavoriteListener::class);
		$context->registerSearchProvider(FilesSearchProvider::class);

		$context->registerNotifierService(Notifier::class);
		$context->registerDashboardWidget(FavoriteWidget::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(Closure::fromCallable([$this, 'registerCollaboration']));
		$context->injectFn([Listener::class, 'register']);
		$this->registerTemplates();
		$this->registerHooks();
	}

	private function registerCollaboration(IProviderManager $providerManager): void {
		$providerManager->registerResourceProvider(ResourceProvider::class);
	}

	private function registerTemplates(): void {
		$templateManager = \OC_Helper::getFileTemplateManager();
		$templateManager->registerTemplate('application/vnd.oasis.opendocument.presentation', 'core/templates/filetemplates/template.odp');
		$templateManager->registerTemplate('application/vnd.oasis.opendocument.text', 'core/templates/filetemplates/template.odt');
		$templateManager->registerTemplate('application/vnd.oasis.opendocument.spreadsheet', 'core/templates/filetemplates/template.ods');
	}

	private function registerHooks(): void {
		Util::connectHook('\OCP\Config', 'js', '\OCA\Files\App', 'extendJsConfig');
	}
}
