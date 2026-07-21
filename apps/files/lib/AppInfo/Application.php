<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files\AppInfo;

use OC\Core\Sharing\Permission\EditSharePermissionPreset;
use OC\Core\Sharing\Permission\ViewSharePermissionPreset;
use OC\Core\Sharing\Property\ExpirationDateSharePropertyType;
use OC\Core\Sharing\Property\LabelSharePropertyType;
use OC\Core\Sharing\Property\NoteSharePropertyType;
use OC\Core\Sharing\Property\PasswordSharePropertyType;
use OC\Core\Sharing\Recipient\TokenShareRecipientType;
use OCA\Files\AdvancedCapabilities;
use OCA\Files\Capabilities;
use OCA\Files\Collaboration\Resources\Listener;
use OCA\Files\Collaboration\Resources\ResourceProvider;
use OCA\Files\ConfigLexicon;
use OCA\Files\Dashboard\FavoriteWidget;
use OCA\Files\DirectEditingCapabilities;
use OCA\Files\Event\LoadSearchPlugins;
use OCA\Files\Event\LoadSidebar;
use OCA\Files\Listener\LoadSearchPluginsListener;
use OCA\Files\Listener\LoadSidebarListener;
use OCA\Files\Listener\NodeAddedToFavoriteListener;
use OCA\Files\Listener\NodeRemovedFromFavoriteListener;
use OCA\Files\Listener\RenderReferenceEventListener;
use OCA\Files\Listener\RestrictInteractionListener;
use OCA\Files\Listener\SyncLivePhotosListener;
use OCA\Files\Listener\UserFirstTimeLoggedInListener;
use OCA\Files\Notification\Notifier;
use OCA\Files\Search\FilesSearchProvider;
use OCA\Files\Sharing\Permission\NodeCreateSharePermissionType;
use OCA\Files\Sharing\Permission\NodeDeleteSharePermissionType;
use OCA\Files\Sharing\Permission\NodeDownloadSharePermissionType;
use OCA\Files\Sharing\Permission\NodeReadSharePermissionType;
use OCA\Files\Sharing\Permission\NodeUpdateSharePermissionType;
use OCA\Files\Sharing\Property\NodeGridViewSharePropertyType;
use OCA\Files\Sharing\Source\NodeShareSourceType;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\Collaboration\Resources\IProviderManager;
use OCP\Files\Cache\CacheEntriesRemovedEvent;
use OCP\Files\Events\Node\BeforeNodeCopiedEvent;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\BeforeNodeRenamedEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\NodeAddedToFavorite;
use OCP\Files\Events\NodeRemovedFromFavorite;
use OCP\Interaction\RestrictInteractionEvent;
use OCP\Server;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use OCP\Share\Events\ShareDeletedFromSelfEvent;
use OCP\Sharing\ISharingRegistry;
use OCP\User\Events\UserFirstTimeLoggedInEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'files';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	#[\Override]
	public function register(IRegistrationContext $context): void {
		/*
		 * Register capabilities
		 */
		$context->registerCapability(Capabilities::class);
		$context->registerCapability(AdvancedCapabilities::class);
		$context->registerCapability(DirectEditingCapabilities::class);

		$context->registerEventListener(LoadSidebar::class, LoadSidebarListener::class);
		$context->registerEventListener(RenderReferenceEvent::class, RenderReferenceEventListener::class);
		$context->registerEventListener(BeforeNodeRenamedEvent::class, SyncLivePhotosListener::class);
		$context->registerEventListener(BeforeNodeDeletedEvent::class, SyncLivePhotosListener::class);
		$context->registerEventListener(CacheEntriesRemovedEvent::class, SyncLivePhotosListener::class, 1); // Ensure this happen before the metadata are deleted.
		$context->registerEventListener(BeforeNodeCopiedEvent::class, SyncLivePhotosListener::class);
		$context->registerEventListener(NodeCopiedEvent::class, SyncLivePhotosListener::class);
		$context->registerEventListener(LoadSearchPlugins::class, LoadSearchPluginsListener::class);
		$context->registerEventListener(NodeAddedToFavorite::class, NodeAddedToFavoriteListener::class);
		$context->registerEventListener(NodeRemovedFromFavorite::class, NodeRemovedFromFavoriteListener::class);
		$context->registerEventListener(UserFirstTimeLoggedInEvent::class, UserFirstTimeLoggedInListener::class);

		$context->registerEventListener(ShareCreatedEvent::class, Listener::class);
		$context->registerEventListener(ShareDeletedEvent::class, Listener::class);
		$context->registerEventListener(ShareDeletedFromSelfEvent::class, Listener::class);

		$context->registerSearchProvider(FilesSearchProvider::class);

		$context->registerNotifierService(Notifier::class);
		$context->registerDashboardWidget(FavoriteWidget::class);

		$context->registerConfigLexicon(ConfigLexicon::class);

		$context->registerEventListener(RestrictInteractionEvent::class, RestrictInteractionListener::class);

		$registry = Server::get(ISharingRegistry::class);

		$registry->registerSourceType(Server::get(NodeShareSourceType::class));
		$registry->markPropertyTypeCompatibleWithSourceType(ExpirationDateSharePropertyType::class, NodeShareSourceType::class);
		$registry->markPropertyTypeCompatibleWithSourceType(LabelSharePropertyType::class, NodeShareSourceType::class);
		$registry->markPropertyTypeCompatibleWithSourceType(NoteSharePropertyType::class, NodeShareSourceType::class);
		$registry->markPropertyTypeCompatibleWithSourceType(PasswordSharePropertyType::class, NodeShareSourceType::class);

		$registry->registerPropertyType(new NodeGridViewSharePropertyType());
		$registry->markPropertyTypeCompatibleWithSourceType(NodeGridViewSharePropertyType::class, NodeShareSourceType::class);
		$registry->markPropertyTypeCompatibleWithRecipientType(NodeGridViewSharePropertyType::class, TokenShareRecipientType::class);

		$registry->registerPermissionType(NodeShareSourceType::class, Server::get(NodeCreateSharePermissionType::class));
		$registry->markPermissionTypeCompatibleWithPermissionPreset(NodeCreateSharePermissionType::class, EditSharePermissionPreset::class);

		$registry->registerPermissionType(NodeShareSourceType::class, new NodeReadSharePermissionType());
		$registry->markPermissionTypeCompatibleWithPermissionPreset(NodeReadSharePermissionType::class, ViewSharePermissionPreset::class);
		$registry->markPermissionTypeCompatibleWithPermissionPreset(NodeReadSharePermissionType::class, EditSharePermissionPreset::class);

		$registry->registerPermissionType(NodeShareSourceType::class, Server::get(NodeUpdateSharePermissionType::class));
		$registry->markPermissionTypeCompatibleWithPermissionPreset(NodeUpdateSharePermissionType::class, EditSharePermissionPreset::class);

		$registry->registerPermissionType(NodeShareSourceType::class, Server::get(NodeDeleteSharePermissionType::class));
		$registry->markPermissionTypeCompatibleWithPermissionPreset(NodeDeleteSharePermissionType::class, EditSharePermissionPreset::class);

		$registry->registerPermissionType(NodeShareSourceType::class, Server::get(NodeDownloadSharePermissionType::class));
		$registry->markPermissionTypeCompatibleWithPermissionPreset(NodeDownloadSharePermissionType::class, ViewSharePermissionPreset::class);
		$registry->markPermissionTypeCompatibleWithPermissionPreset(NodeDownloadSharePermissionType::class, EditSharePermissionPreset::class);
	}

	#[\Override]
	public function boot(IBootContext $context): void {
		$context->injectFn(\Closure::fromCallable($this->registerCollaboration(...)));
	}

	private function registerCollaboration(IProviderManager $providerManager): void {
		$providerManager->registerResourceProvider(ResourceProvider::class);
	}
}
