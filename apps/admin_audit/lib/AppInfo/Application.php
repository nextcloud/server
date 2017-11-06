<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\AdminAudit\AppInfo;

use OC\Files\Filesystem;
use OC\Files\Node\File;
use OC\Group\Manager;
use OC\User\Session;
use OCA\AdminAudit\Actions\AppManagement;
use OCA\AdminAudit\Actions\Auth;
use OCA\AdminAudit\Actions\Console;
use OCA\AdminAudit\Actions\Files;
use OCA\AdminAudit\Actions\GroupManagement;
use OCA\AdminAudit\Actions\Sharing;
use OCA\AdminAudit\Actions\Trashbin;
use OCA\AdminAudit\Actions\UserManagement;
use OCA\AdminAudit\Actions\Versions;
use OCP\App\ManagerEvent;
use OCP\AppFramework\App;
use OCP\Console\ConsoleEvent;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IPreview;
use OCP\IUserSession;
use OCP\Util;
use Symfony\Component\EventDispatcher\GenericEvent;

class Application extends App {

	public function __construct() {
		parent::__construct('admin_audit');
	}

	public function register() {
		$this->registerHooks();
	}

	/**
	 * Register hooks in order to log them
	 */
	protected function registerHooks() {
		$logger = $this->getContainer()->getServer()->getLogger();

		$this->userManagementHooks($logger);
		$this->groupHooks($logger);
		$this->authHooks($logger);

		$this->consoleHooks($logger);
		$this->appHooks($logger);

		$this->sharingHooks($logger);

		$this->fileHooks($logger);
		$this->trashbinHooks($logger);
		$this->versionsHooks($logger);
	}

	protected function userManagementHooks(ILogger $logger) {
		$userActions = new UserManagement($logger);

		Util::connectHook('OC_User', 'post_createUser',	$userActions, 'create');
		Util::connectHook('OC_User', 'post_deleteUser',	$userActions, 'delete');
		Util::connectHook('OC_User', 'changeUser',	$userActions, 'change');

		/** @var IUserSession|Session $userSession */
		$userSession = $this->getContainer()->getServer()->getUserSession();
		$userSession->listen('\OC\User', 'postSetPassword', [$userActions, 'setPassword']);
	}

	protected function groupHooks(ILogger $logger)  {
		$groupActions = new GroupManagement($logger);

		/** @var IGroupManager|Manager $groupManager */
		$groupManager = $this->getContainer()->getServer()->getGroupManager();
		$groupManager->listen('\OC\Group', 'postRemoveUser',  [$groupActions, 'removeUser']);
		$groupManager->listen('\OC\Group', 'postAddUser',  [$groupActions, 'addUser']);
		$groupManager->listen('\OC\Group', 'postDelete',  [$groupActions, 'deleteGroup']);
		$groupManager->listen('\OC\Group', 'postCreate',  [$groupActions, 'createGroup']);
	}

	protected function sharingHooks(ILogger $logger) {
		$shareActions = new Sharing($logger);

		Util::connectHook('OCP\Share', 'post_shared', $shareActions, 'shared');
		Util::connectHook('OCP\Share', 'post_unshare', $shareActions, 'unshare');
		Util::connectHook('OCP\Share', 'post_update_permissions', $shareActions, 'updatePermissions');
		Util::connectHook('OCP\Share', 'post_update_password', $shareActions, 'updatePassword');
		Util::connectHook('OCP\Share', 'post_set_expiration_date', $shareActions, 'updateExpirationDate');
		Util::connectHook('OCP\Share', 'share_link_access', $shareActions, 'shareAccessed');
	}

	protected function authHooks(ILogger $logger) {
		$authActions = new Auth($logger);

		Util::connectHook('OC_User', 'pre_login', $authActions, 'loginAttempt');
		Util::connectHook('OC_User', 'post_login', $authActions, 'loginSuccessful');
		Util::connectHook('OC_User', 'logout', $authActions, 'logout');
	}

	protected function appHooks(ILogger $logger) {

		$eventDispatcher = $this->getContainer()->getServer()->getEventDispatcher();
		$eventDispatcher->addListener(ManagerEvent::EVENT_APP_ENABLE, function(ManagerEvent $event) use ($logger) {
			$appActions = new AppManagement($logger);
			$appActions->enableApp($event->getAppID());
		});
		$eventDispatcher->addListener(ManagerEvent::EVENT_APP_ENABLE_FOR_GROUPS, function(ManagerEvent $event) use ($logger) {
			$appActions = new AppManagement($logger);
			$appActions->enableAppForGroups($event->getAppID(), $event->getGroups());
		});
		$eventDispatcher->addListener(ManagerEvent::EVENT_APP_DISABLE, function(ManagerEvent $event) use ($logger) {
			$appActions = new AppManagement($logger);
			$appActions->disableApp($event->getAppID());
		});

	}

	protected function consoleHooks(ILogger $logger) {
		$eventDispatcher = $this->getContainer()->getServer()->getEventDispatcher();
		$eventDispatcher->addListener(ConsoleEvent::EVENT_RUN, function(ConsoleEvent $event) use ($logger) {
			$appActions = new Console($logger);
			$appActions->runCommand($event->getArguments());
		});
	}

	protected function fileHooks(ILogger $logger) {
		$fileActions = new Files($logger);
		$eventDispatcher = $this->getContainer()->getServer()->getEventDispatcher();
		$eventDispatcher->addListener(
			IPreview::EVENT,
			function(GenericEvent $event) use ($fileActions) {
				/** @var File $file */
				$file = $event->getSubject();
				$fileActions->preview([
					'path' => substr($file->getInternalPath(), 5),
					'width' => $event->getArguments()['width'],
					'height' => $event->getArguments()['height'],
					'crop' => $event->getArguments()['crop'],
					'mode'  => $event->getArguments()['mode']
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

	protected function versionsHooks(ILogger $logger) {
		$versionsActions = new Versions($logger);
		Util::connectHook('\OCP\Versions', 'rollback', $versionsActions, 'rollback');
		Util::connectHook('\OCP\Versions', 'delete',$versionsActions, 'delete');
	}

	protected function trashbinHooks(ILogger $logger) {
		$trashActions = new Trashbin($logger);
		Util::connectHook('\OCP\Trashbin', 'preDelete', $trashActions, 'delete');
		Util::connectHook('\OCA\Files_Trashbin\Trashbin', 'post_restore', $trashActions, 'restore');
	}
}
