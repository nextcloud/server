<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\AppInfo;

use OCA\AdminAudit\Actions\Auth;
use OCA\AdminAudit\Actions\Console;
use OCA\AdminAudit\Actions\Files;
use OCA\AdminAudit\Actions\Sharing;
use OCA\AdminAudit\Actions\TagManagement;
use OCA\AdminAudit\Actions\Trashbin;
use OCA\AdminAudit\Actions\Versions;
use OCA\AdminAudit\AuditLogger;
use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\AppManagementEventListener;
use OCA\AdminAudit\Listener\AuthEventListener;
use OCA\AdminAudit\Listener\CacheEventListener;
use OCA\AdminAudit\Listener\ConsoleEventListener;
use OCA\AdminAudit\Listener\CriticalActionPerformedEventListener;
use OCA\AdminAudit\Listener\FileEventListener;
use OCA\AdminAudit\Listener\GroupManagementEventListener;
use OCA\AdminAudit\Listener\SecurityEventListener;
use OCA\AdminAudit\Listener\SharingEventListener;
use OCA\AdminAudit\Listener\UserManagementEventListener;
use OCA\Files_Versions\Events\VersionRestoredEvent;
use OCP\App\Events\AppDisableEvent;
use OCP\App\Events\AppEnableEvent;
use OCP\App\Events\AppUpdateEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Authentication\Events\AnyLoginFailedEvent;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengeFailed;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengePassed;
use OCP\Console\ConsoleEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Cache\CacheEntryInsertedEvent;
use OCP\Files\Cache\CacheEntryRemovedEvent;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\BeforeNodeReadEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Group\Events\GroupCreatedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IConfig;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Log\ILogFactory;
use OCP\Preview\BeforePreviewFetchedEvent;
use OCP\Share;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use OCP\SystemTag\ManagerEvent;
use OCP\User\Events\BeforeUserLoggedInEvent;
use OCP\User\Events\BeforeUserLoggedOutEvent;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\User\Events\UserIdAssignedEvent;
use OCP\User\Events\UserIdUnassignedEvent;
use OCP\User\Events\UserLoggedInEvent;
use OCP\User\Events\UserLoggedInWithCookieEvent;
use OCP\Util;
use Psr\Container\ContainerInterface;

class Application extends App implements IBootstrap {
	public function __construct() {
		parent::__construct('admin_audit');
	}

	public function register(IRegistrationContext $context): void {
		$context->registerService(IAuditLogger::class, function (ContainerInterface $c) {
			return new AuditLogger($c->get(ILogFactory::class), $c->get(IConfig::class));
		});

		$context->registerEventListener(CriticalActionPerformedEvent::class, CriticalActionPerformedEventListener::class);

		// User management events
		$context->registerEventListener(UserCreatedEvent::class, UserManagementEventListener::class);
		$context->registerEventListener(UserDeletedEvent::class, UserManagementEventListener::class);
		$context->registerEventListener(UserChangedEvent::class, UserManagementEventListener::class);
		$context->registerEventListener(PasswordUpdatedEvent::class, UserManagementEventListener::class);
		$context->registerEventListener(UserIdAssignedEvent::class, UserManagementEventListener::class);
		$context->registerEventListener(UserIdUnassignedEvent::class, UserManagementEventListener::class);

		// Group management events
		$context->registerEventListener(UserAddedEvent::class, GroupManagementEventListener::class);
		$context->registerEventListener(UserRemovedEvent::class, GroupManagementEventListener::class);
		$context->registerEventListener(GroupCreatedEvent::class, GroupManagementEventListener::class);
		$context->registerEventListener(GroupDeletedEvent::class, GroupManagementEventListener::class);

		// Sharing events
		$context->registerEventListener(ShareCreatedEvent::class, SharingEventListener::class);
		$context->registerEventListener(ShareDeletedEvent::class, SharingEventListener::class);

		// Auth events
		$context->registerEventListener(BeforeUserLoggedInEvent::class, AuthEventListener::class);
		$context->registerEventListener(UserLoggedInWithCookieEvent::class, AuthEventListener::class);
		$context->registerEventListener(UserLoggedInEvent::class, AuthEventListener::class);
		$context->registerEventListener(BeforeUserLoggedOutEvent::class, AuthEventListener::class);
		$context->registerEventListener(AnyLoginFailedEvent::class, AuthEventListener::class);

		// File events
		$context->registerEventListener(BeforePreviewFetchedEvent::class, FileEventListener::class);
		$context->registerEventListener(VersionRestoredEvent::class, FileEventListener::class);

		// Security events
		$context->registerEventListener(TwoFactorProviderChallengePassed::class, SecurityEventListener::class);
		$context->registerEventListener(TwoFactorProviderChallengeFailed::class, SecurityEventListener::class);

		// App management events
		$context->registerEventListener(AppEnableEvent::class, AppManagementEventListener::class);
		$context->registerEventListener(AppDisableEvent::class, AppManagementEventListener::class);
		$context->registerEventListener(AppUpdateEvent::class, AppManagementEventListener::class);

		// Console events
		$context->registerEventListener(ConsoleEvent::class, ConsoleEventListener::class);

		// Cache events
		$context->registerEventListener(CacheEntryInsertedEvent::class, CacheEventListener::class);
		$context->registerEventListener(CacheEntryRemovedEvent::class, CacheEventListener::class);
	}

	public function boot(IBootContext $context): void {
		/** @var IAuditLogger $logger */
		$logger = $context->getAppContainer()->get(IAuditLogger::class);

		/*
		 * TODO: once the hooks are migrated to lazy events, this should be done
		 *       in \OCA\AdminAudit\AppInfo\Application::register
		 */
		$this->registerLegacyHooks($logger, $context->getServerContainer());
	}

	/**
	 * Register hooks in order to log them
	 */
	private function registerLegacyHooks(IAuditLogger $logger, ContainerInterface $serverContainer): void {
		/** @var IEventDispatcher $eventDispatcher */
		$eventDispatcher = $serverContainer->get(IEventDispatcher::class);
		$this->sharingLegacyHooks($logger);
		$this->fileHooks($logger, $eventDispatcher);
		$this->trashbinHooks($logger);
		$this->versionsHooks($logger);
		$this->tagHooks($logger, $eventDispatcher);
	}

	private function sharingLegacyHooks(IAuditLogger $logger): void {
		$shareActions = new Sharing($logger);

		Util::connectHook(Share::class, 'post_update_permissions', $shareActions, 'updatePermissions');
		Util::connectHook(Share::class, 'post_update_password', $shareActions, 'updatePassword');
		Util::connectHook(Share::class, 'post_set_expiration_date', $shareActions, 'updateExpirationDate');
		Util::connectHook(Share::class, 'share_link_access', $shareActions, 'shareAccessed');
	}

	private function tagHooks(IAuditLogger $logger,
		IEventDispatcher $eventDispatcher): void {
		$eventDispatcher->addListener(ManagerEvent::EVENT_CREATE, function (ManagerEvent $event) use ($logger): void {
			$tagActions = new TagManagement($logger);
			$tagActions->createTag($event->getTag());
		});
	}

	private function fileHooks(IAuditLogger $logger, IEventDispatcher $eventDispatcher): void {
		$fileActions = new Files($logger);

		$eventDispatcher->addListener(
			NodeRenamedEvent::class,
			function (NodeRenamedEvent $event) use ($fileActions): void {
				$fileActions->afterRename($event);
			}
		);

		$eventDispatcher->addListener(
			NodeCreatedEvent::class,
			function (NodeCreatedEvent $event) use ($fileActions): void {
				$fileActions->create($event);
			}
		);

		$eventDispatcher->addListener(
			NodeCopiedEvent::class,
			function (NodeCopiedEvent $event) use ($fileActions): void {
				$fileActions->copy($event);
			}
		);

		$eventDispatcher->addListener(
			NodeWrittenEvent::class,
			function (NodeWrittenEvent $event) use ($fileActions): void {
				$fileActions->write($event);
			}
		);

		$eventDispatcher->addListener(
			BeforeNodeReadEvent::class,
			function (BeforeNodeReadEvent $event) use ($fileActions): void {
				$fileActions->read($event);
			}
		);

		$eventDispatcher->addListener(
			BeforeNodeDeletedEvent::class,
			function (BeforeNodeDeletedEvent $event) use ($fileActions): void {
				$fileActions->delete($event);
			}
		);
	}

	private function versionsHooks(IAuditLogger $logger): void {
		$versionsActions = new Versions($logger);
		Util::connectHook('\OCP\Versions', 'delete', $versionsActions, 'delete');
	}

	private function trashbinHooks(IAuditLogger $logger): void {
		$trashActions = new Trashbin($logger);
		Util::connectHook('\OCP\Trashbin', 'preDelete', $trashActions, 'delete');
		Util::connectHook('\OCA\Files_Trashbin\Trashbin', 'post_restore', $trashActions, 'restore');
	}
}
