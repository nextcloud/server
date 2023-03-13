<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author GrayFix <grayfix@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Tiago Flores <tiago.flores@yahoo.com.br>
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
namespace OCA\AdminAudit\AppInfo;

use OC\Files\Filesystem;
use OCA\AdminAudit\Actions\Files;
use OCA\AdminAudit\Actions\Sharing;
use OCA\AdminAudit\Actions\Trashbin;
use OCA\AdminAudit\Actions\Versions;
use OCA\AdminAudit\AuditLogger;
use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\AppManagementEventListener;
use OCA\AdminAudit\Listener\AuthEventListener;
use OCA\AdminAudit\Listener\ConsoleEventListener;
use OCA\AdminAudit\Listener\CriticalActionPerformedEventListener;
use OCA\AdminAudit\Listener\FileEventListener;
use OCA\AdminAudit\Listener\GroupManagementEventListener;
use OCA\AdminAudit\Listener\SecurityEventListener;
use OCA\AdminAudit\Listener\SharingEventListener;
use OCA\AdminAudit\Listener\UserManagementEventListener;
use OCP\App\Events\AppDisableEvent;
use OCP\App\Events\AppEnableEvent;
use OCP\App\Events\AppUpdateEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserDisabled;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserEnabled;
use OCP\Console\ConsoleEventV2;
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
			return new AuditLogger($c->get(ILogFactory::class), $c->get(Iconfig::class));
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

		// File events
		$context->registerEventListener(BeforePreviewFetchedEvent::class, FileEventListener::class);

		// Security events
		$context->registerEventListener(TwoFactorProviderForUserEnabled::class, SecurityEventListener::class);
		$context->registerEventListener(TwoFactorProviderForUserDisabled::class, SecurityEventListener::class);

		// App management events
		$context->registerEventListener(AppEnableEvent::class, AppManagementEventListener::class);
		$context->registerEventListener(AppDisableEvent::class, AppManagementEventListener::class);
		$context->registerEventListener(AppUpdateEvent::class, AppManagementEventListener::class);

		// Console events
		$context->registerEventListener(ConsoleEventV2::class, ConsoleEventListener::class);
	}

	public function boot(IBootContext $context): void {
		/** @var IAuditLogger $logger */
		$logger = $context->getAppContainer()->get(IAuditLogger::class);

		/*
		 * TODO: once the hooks are migrated to lazy events, this should be done
		 *       in \OCA\AdminAudit\AppInfo\Application::register
		 */
		$this->registerLegacyHooks($logger);
	}

	/**
	 * Register hooks in order to log them
	 */
	private function registerLegacyHooks(IAuditLogger $logger): void {

		$this->sharingLegacyHooks($logger);

		$this->fileHooks($logger);
		$this->trashbinHooks($logger);
		$this->versionsHooks($logger);
	}

	private function sharingLegacyHooks(IAuditLogger $logger): void {
		$shareActions = new Sharing($logger);

		Util::connectHook(Share::class, 'post_update_permissions', $shareActions, 'updatePermissions');
		Util::connectHook(Share::class, 'post_update_password', $shareActions, 'updatePassword');
		Util::connectHook(Share::class, 'post_set_expiration_date', $shareActions, 'updateExpirationDate');
		Util::connectHook(Share::class, 'share_link_access', $shareActions, 'shareAccessed');
	}

	private function fileHooks(IAuditLogger $logger): void {
		$fileActions = new Files($logger);

		Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_post_rename,
			$fileActions,
			'rename'
		);
		Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_post_create,
			$fileActions,
			'create'
		);
		Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_post_copy,
			$fileActions,
			'copy'
		);
		Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_post_write,
			$fileActions,
			'write'
		);
		Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_post_update,
			$fileActions,
			'update'
		);
		Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_read,
			$fileActions,
			'read'
		);
		Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_delete,
			$fileActions,
			'delete'
		);
	}

	private function versionsHooks(IAuditLogger $logger): void {
		$versionsActions = new Versions($logger);
		Util::connectHook('\OCP\Versions', 'rollback', $versionsActions, 'rollback');
		Util::connectHook('\OCP\Versions', 'delete', $versionsActions, 'delete');
	}

	private function trashbinHooks(IAuditLogger $logger): void {
		$trashActions = new Trashbin($logger);
		Util::connectHook('\OCP\Trashbin', 'preDelete', $trashActions, 'delete');
		Util::connectHook('\OCA\Files_Trashbin\Trashbin', 'post_restore', $trashActions, 'restore');
	}
}
