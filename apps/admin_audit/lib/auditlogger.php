<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Admin_Audit;

use OC\Files\Filesystem;
use OC\Files\Node\File;
use OCA\Admin_Audit\Actions\Auth;
use OCA\Admin_Audit\Actions\Files;
use OCA\Admin_Audit\Actions\GroupManagement;
use OCA\Admin_Audit\Actions\Sharing;
use OCA\Admin_Audit\Actions\Trashbin;
use OCA\Admin_Audit\Actions\UserManagement;
use OCA\Admin_Audit\Actions\Versions;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IPreview;
use OCP\IUserSession;
use OCP\Util;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

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
	 * @param EventDispatcherInterface $eventDispatcher
	 */
	public function __construct(ILogger $logger,
								IUserSession $userSession, 
								IGroupManager $groupManager,
								EventDispatcherInterface $eventDispatcher) {
		$this->logger = $logger;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * Register hooks in order to log them
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
	 * Connect to user management hooks
	 */
	private function userManagementHooks() {
		$userActions = new UserManagement($this->logger);

		Util::connectHook('OC_User', 'post_createUser',	$userActions, 'create');
		Util::connectHook('OC_User', 'post_deleteUser',	$userActions, 'delete');
		Util::connectHook('OC_User', 'changeUser',	$userActions, 'change');
		$this->userSession->listen('\OC\User', 'postSetPassword', [$userActions, 'setPassword']);
	}
	
	private function groupHooks()  {
		$groupActions = new GroupManagement($this->logger);
		$this->groupManager->listen('\OC\Group', 'postRemoveUser',  [$groupActions, 'removeUser']);
		$this->groupManager->listen('\OC\Group', 'postAddUser',  [$groupActions, 'addUser']);
		$this->groupManager->listen('\OC\Group', 'postDelete',  [$groupActions, 'deleteGroup']);
		$this->groupManager->listen('\OC\Group', 'postCreate',  [$groupActions, 'createGroup']);
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
	 * Connect to file hooks
	 */
	private function fileHooks() {
		$fileActions = new Files($this->logger);
		$this->eventDispatcher->addListener(
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

	public function versionsHooks() {
		$versionsActions = new Versions($this->logger);
		Util::connectHook('\OCP\Versions', 'rollback', $versionsActions, 'rollback');
		Util::connectHook('\OCP\Versions', 'delete',$versionsActions, 'delete');
	}

	/**
	 * Connect to trash bin hooks
	 */
	private function trashbinHooks() {
		$trashActions = new Trashbin($this->logger);
		Util::connectHook('\OCP\Trashbin', 'preDelete', $trashActions, 'delete');
		Util::connectHook('\OCA\Files_Trashbin\Trashbin', 'post_restore', $trashActions, 'restore');
	}

}
