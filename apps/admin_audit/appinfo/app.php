<?php
/**
 * @author Lukas Reschke <lukas@statuscode.ch>
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

$logger = \OC::$server->getLogger();

logUserManagement(
	$logger,
	\OC::$server->getUserSession()
);
logFileActions($logger);
logAuthEvents($logger);
logShareEvents($logger);

/**
 * Logs sharing events
 *
 * @param \OCP\ILogger $logger
 */
function logShareEvents($logger) {
	$shareActions = new \OCA\Admin_Audit\Actions\Sharing(
		$logger
	);

	OCP\Util::connectHook('OCP\Share', 'post_shared', $shareActions, 'shared');
	OCP\Util::connectHook('OCP\Share', 'post_unshare', $shareActions, 'unshare');
	OCP\Util::connectHook('OCP\Share', 'post_update_permissions', $shareActions, 'updatePermissions');
	OCP\Util::connectHook('OCP\Share', 'post_update_password', $shareActions, 'updatePassword');
	OCP\Util::connectHook('OCP\Share', 'post_set_expiration_date', $shareActions, 'updateExpirationDate');
	OCP\Util::connectHook('OCP\Share', 'share_link_access', $shareActions, 'shareAccessed');
}

/**
 * Log authentication event related actions
 *
 * @param \OCP\ILogger $logger
 */
function logAuthEvents($logger) {
	$authActions = new \OCA\Admin_Audit\Actions\Auth(
		$logger
	);
	OCP\Util::connectHook('OC_User', 'pre_login', $authActions, 'loginAttempt');
	OCP\Util::connectHook('OC_User', 'post_login', $authActions, 'loginSuccessful');
	OCP\Util::connectHook('OC_User', 'logout', $authActions, 'logout');
}

/**
 * Log user management related actions
 *
 * @param \OCP\ILogger $logger
 * @param \OC\User\Session $userSession
 */
function logUserManagement($logger, $userSession) {
	$userActions = new \OCA\Admin_Audit\Actions\UserManagement(
		$logger
	);

	OCP\Util::connectHook(
		'OC_User',
		'post_createUser',
		$userActions,
		'create'
	);
	OCP\Util::connectHook(
		'OC_User',
		'post_deleteUser',
		$userActions,
		'delete'
	);
	$userSession->listen('\OC\User', 'postSetPassword', [$userActions, 'setPassword']);
}

/**
 * Log file related actions
 *
 * @param \OCP\ILogger $logger
 */
function logFileActions($logger) {
	$fileActions = new \OCA\Admin_Audit\Actions\Files(
		$logger
	);

	OCP\Util::connectHook(
		OC\Files\Filesystem::CLASSNAME,
		OC\Files\Filesystem::signal_post_rename,
		$fileActions,
		'rename'
	);
	OCP\Util::connectHook(
		OC\Files\Filesystem::CLASSNAME,
		OC\Files\Filesystem::signal_post_create,
		$fileActions,
		'create'
	);
	OCP\Util::connectHook(
		OC\Files\Filesystem::CLASSNAME,
		OC\Files\Filesystem::signal_post_copy,
		$fileActions,
		'copy'
	);
	OCP\Util::connectHook(
		OC\Files\Filesystem::CLASSNAME,
		OC\Files\Filesystem::signal_post_write,
		$fileActions,
		'write'
	);
	OCP\Util::connectHook(
		OC\Files\Filesystem::CLASSNAME,
		OC\Files\Filesystem::signal_post_update,
		$fileActions,
		'update'
	);
	OCP\Util::connectHook(
		OC\Files\Filesystem::CLASSNAME,
		OC\Files\Filesystem::signal_read,
		$fileActions,
		'read'
	);
	OCP\Util::connectHook(
		OC\Files\Filesystem::CLASSNAME,
		OC\Files\Filesystem::signal_delete,
		$fileActions,
		'delete'
	);
}
