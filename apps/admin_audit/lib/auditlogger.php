<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
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


namespace OCA\Admin_Audit;


use OC\Files\Filesystem;
use OCA\Admin_Audit\Actions\Auth;
use OCA\Admin_Audit\Actions\Files;
use OCA\Admin_Audit\Actions\GroupManagement;
use OCA\Admin_Audit\Actions\Sharing;
use OCA\Admin_Audit\Actions\Trashbin;
use OCA\Admin_Audit\Actions\UserManagement;
use OCA\Admin_Audit\Actions\Versions;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUserSession;
use OCP\Util;

class AuditLogger {

	/** @var ILogger */
	private $logger;

	/** @var IUserSession */
	private $userSession;
	
	/** @var IGroupManager */
	private $groupManager;

	/**
	 * AuditLogger constructor.
	 *
	 * @param ILogger $logger
	 * @param IUserSession $userSession
	 * @param IGroupManager $groupManager
	 */
	public function __construct(ILogger $logger,
								IUserSession $userSession, 
								IGroupManager $groupManager) {
		$this->logger = $logger;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
	}

	/**
	 * register hooks in order to log them
	 */
	public function registerHooks() {
		$this->userManagementHooks();
		$this->groupHooks();
		$this->sharingHooks();
		$this->authHooks();
		$this->fileHooks();
		$this->trashbinHooks();
		$this->versionsHooks();
	}

	/**
	 * connect to user management hooks
	 */
	private function userManagementHooks() {
		$userActions = new UserManagement($this->logger);

		Util::connectHook('OC_User', 'post_createUser',	$userActions, 'create');
		Util::connectHook('OC_User', 'post_deleteUser',	$userActions, 'delete');
		$this->userSession->listen('\OC\User', 'postSetPassword', [$userActions, 'setPassword']);
	}
	
	private function groupHooks()  {
		$groupActions = new GroupManagement($this->logger);
		$this->groupManager->listen('\OC\Group', 'postRemoveUser',  [$groupActions, 'removeUser']);
		$this->groupManager->listen('\OC\Group', 'postAddUser',  [$groupActions, 'addUser']);
	}

	/**
	 * connect to sharing events
	 */
	private function sharingHooks() {
		$shareActions = new Sharing($this->logger);

		Util::connectHook('OCP\Share', 'post_shared', $shareActions, 'shared');
		Util::connectHook('OCP\Share', 'post_unshare', $shareActions, 'unshare');
		Util::connectHook('OCP\Share', 'post_update_permissions', $shareActions, 'updatePermissions');
		Util::connectHook('OCP\Share', 'post_update_password', $shareActions, 'updatePassword');
		Util::connectHook('OCP\Share', 'post_set_expiration_date', $shareActions, 'updateExpirationDate');
		Util::connectHook('OCP\Share', 'share_link_access', $shareActions, 'shareAccessed');
	}

	/**
	 * connect to authentication event and related actions
	 */
	private function authHooks() {
		$authActions = new Auth($this->logger);

		Util::connectHook('OC_User', 'pre_login', $authActions, 'loginAttempt');
		Util::connectHook('OC_User', 'post_login', $authActions, 'loginSuccessful');
		Util::connectHook('OC_User', 'logout', $authActions, 'logout');
	}


	/**
	 * connect to file hooks
	 */
	private function fileHooks() {
		$fileActions = new Files($this->logger);

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

	public function versionsHooks() {
		$versionsActions = new Versions($this->logger);
		Util::connectHook('\OCP\Versions', 'rollback', $versionsActions, 'rollback');
		Util::connectHook('\OCP\Versions', 'delete',$versionsActions, 'delete');
	}

	/**
	 * connect to trash bin hooks
	 */
	private function trashbinHooks() {
		$trashActions = new Trashbin($this->logger);
		Util::connectHook('\OCP\Trashbin', 'preDelete', $trashActions, 'delete');
		Util::connectHook('\OCA\Files_Trashbin\Trashbin', 'post_restore', $trashActions, 'restore');
	}

}
