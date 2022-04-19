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

use Closure;
use OC\Files\Filesystem;
use OC\Files\Node\File;
use OC\Group\Manager as GroupManager;
use OC\User\Session as UserSession;
use OCA\AdminAudit\Actions\AppManagement;
use OCA\AdminAudit\Actions\Auth;
use OCA\AdminAudit\Actions\Console;
use OCA\AdminAudit\Actions\Files;
use OCA\AdminAudit\Actions\GroupManagement;
use OCA\AdminAudit\Actions\Security;
use OCA\AdminAudit\Actions\Sharing;
use OCA\AdminAudit\Actions\Trashbin;
use OCA\AdminAudit\Actions\UserManagement;
use OCA\AdminAudit\Actions\Versions;
use OCA\AdminAudit\AuditLogger;
use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\CriticalActionPerformedEventListener;
use OCP\App\ManagerEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Console\ConsoleEvent;
use OCP\Group\Events\GroupCreatedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IPreview;
use OCP\IServerContainer;
use OCP\IUserSession;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Log\ILogFactory;
use OCP\Share;
use OCP\User\Events\BeforeUserLoggedInEvent;
use OCP\User\Events\UserIdAssignedEvent;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\User\Events\UserIdUnassignedEvent;
use OCP\User\Events\UserLoggedInEvent;
use OCP\User\Events\UserLoggedOutEvent;
use OCP\Util;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class Application extends App implements IBootstrap {

	/** @var LoggerInterface */
	protected $logger;

	public function __construct() {
		parent::__construct('admin_audit');
	}

	public function register(IRegistrationContext $context): void {
		$context->registerService(IAuditLogger::class, function (ContainerInterface $c) {
			return new AuditLogger($c->get(ILogFactory::class), $c->get(Iconfig::class));
		});

		$context->registerEventListener(CriticalActionPerformedEvent::class, CriticalActionPerformedEventListener::class);

		// User management
		$context->registerEventListener(UserCreatedEvent::class, UserManagement::class);
		$context->registerEventListener(UserDeletedEvent::class, UserManagement::class);
		$context->registerEventListener(UserChangedEvent::class, UserManagement::class);
		$context->registerEventListener(PasswordUpdatedEvent::class, UserManagement::class);
		$context->registerEventListener(UserIdAssignedEvent::class, UserManagement::class);
		$context->registerEventListener(UserIdUnassignedEvent::class, UserManagement::class);

		// Group management
		$context->registerEventListener(GroupCreatedEvent::class, GroupManagement::class);
		$context->registerEventListener(GroupDeletedEvent::class, GroupManagement::class);
		$context->registerEventListener(UserAddedEvent::class, GroupManagement::class);
		$context->registerEventListener(UserRemovedEvent::class, GroupManagement::class);

		// Authentication management
		$context->registerEventListener(UserLoggedInEvent::class, Auth::class);
		$context->registerEventListener(BeforeUserLoggedInEvent::class, Auth::class);
		$context->registerEventListener(UserLoggedOutEvent::class, Auth::class);

	}

	public function boot(IBootContext $context): void {
		/** @var IAuditLogger $logger */
		$logger = $context->getAppContainer()->get(IAuditLogger::class);

		/*
		 * TODO: once the hooks are migrated to lazy events, this should be done
		 *       in \OCA\AdminAudit\AppInfo\Application::register
		 */
		$this->registerHooks($logger, $context->getServerContainer());
	}

	/**
	 * Register hooks in order to log them
	 */
	private function registerHooks(IAuditLogger $logger,
									 IServerContainer $serverContainer): void {
		/** @var EventDispatcherInterface $eventDispatcher */
		$eventDispatcher = $serverContainer->get(EventDispatcherInterface::class);
		$this->consoleHooks($logger, $eventDispatcher);
		$this->appHooks($logger, $eventDispatcher);

		$this->sharingHooks($logger);

		$this->fileHooks($logger, $eventDispatcher);
		$this->trashbinHooks($logger);
		$this->versionsHooks($logger);

		$this->securityHooks($logger, $eventDispatcher);
	}

	private function sharingHooks(IAuditLogger $logger): void {
		$shareActions = new Sharing($logger);

		Util::connectHook(Share::class, 'post_shared', $shareActions, 'shared');
		Util::connectHook(Share::class, 'post_unshare', $shareActions, 'unshare');
		Util::connectHook(Share::class, 'post_unshareFromSelf', $shareActions, 'unshare');
		Util::connectHook(Share::class, 'post_update_permissions', $shareActions, 'updatePermissions');
		Util::connectHook(Share::class, 'post_update_password', $shareActions, 'updatePassword');
		Util::connectHook(Share::class, 'post_set_expiration_date', $shareActions, 'updateExpirationDate');
		Util::connectHook(Share::class, 'share_link_access', $shareActions, 'shareAccessed');
	}

	private function appHooks(IAuditLogger $logger,
							  EventDispatcherInterface $eventDispatcher): void {
		$eventDispatcher->addListener(ManagerEvent::EVENT_APP_ENABLE, function (ManagerEvent $event) use ($logger) {
			$appActions = new AppManagement($logger);
			$appActions->enableApp($event->getAppID());
		});
		$eventDispatcher->addListener(ManagerEvent::EVENT_APP_ENABLE_FOR_GROUPS, function (ManagerEvent $event) use ($logger) {
			$appActions = new AppManagement($logger);
			$appActions->enableAppForGroups($event->getAppID(), $event->getGroups());
		});
		$eventDispatcher->addListener(ManagerEvent::EVENT_APP_DISABLE, function (ManagerEvent $event) use ($logger) {
			$appActions = new AppManagement($logger);
			$appActions->disableApp($event->getAppID());
		});
	}

	private function consoleHooks(IAuditLogger $logger,
								  EventDispatcherInterface $eventDispatcher): void {
		$eventDispatcher->addListener(ConsoleEvent::EVENT_RUN, function (ConsoleEvent $event) use ($logger) {
			$appActions = new Console($logger);
			$appActions->runCommand($event->getArguments());
		});
	}

	private function fileHooks(IAuditLogger $logger,
							   EventDispatcherInterface $eventDispatcher): void {
		$fileActions = new Files($logger);
		$eventDispatcher->addListener(
			IPreview::EVENT,
			function (GenericEvent $event) use ($fileActions) {
				/** @var File $file */
				$file = $event->getSubject();
				$fileActions->preview([
					'path' => mb_substr($file->getInternalPath(), 5),
					'width' => $event->getArguments()['width'],
					'height' => $event->getArguments()['height'],
					'crop' => $event->getArguments()['crop'],
					'mode' => $event->getArguments()['mode']
				]);
			}
		);

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

	private function securityHooks(IAuditLogger $logger,
								   EventDispatcherInterface $eventDispatcher): void {
		$eventDispatcher->addListener(IProvider::EVENT_SUCCESS, function (GenericEvent $event) use ($logger) {
			$security = new Security($logger);
			$security->twofactorSuccess($event->getSubject(), $event->getArguments());
		});
		$eventDispatcher->addListener(IProvider::EVENT_FAILED, function (GenericEvent $event) use ($logger) {
			$security = new Security($logger);
			$security->twofactorFailed($event->getSubject(), $event->getArguments());
		});
	}
}
