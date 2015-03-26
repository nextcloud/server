<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author cmeh <cmeh@users.noreply.github.com>
 * @author Florin Peter <github@florin-peter.de>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Sam Tuke <mail@samtuke.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Settings\ChangePassword;

class Controller {
	public static function changePersonalPassword($args) {
		// Check if we are an user
		\OC_JSON::callCheck();
		\OC_JSON::checkLoggedIn();

		$username = \OC_User::getUser();
		$password = isset($_POST['personal-password']) ? $_POST['personal-password'] : null;
		$oldPassword = isset($_POST['oldpassword']) ? $_POST['oldpassword'] : '';

		if (!\OC_User::checkPassword($username, $oldPassword)) {
			$l = new \OC_L10n('settings');
			\OC_JSON::error(array("data" => array("message" => $l->t("Wrong password")) ));
			exit();
		}
		if (!is_null($password) && \OC_User::setPassword($username, $password)) {
			\OC_JSON::success();
		} else {
			\OC_JSON::error();
		}
	}

	public static function changeUserPassword($args) {
		// Check if we are an user
		\OC_JSON::callCheck();
		\OC_JSON::checkLoggedIn();

		if (isset($_POST['username'])) {
			$username = $_POST['username'];
		} else {
			$l = new \OC_L10n('settings');
			\OC_JSON::error(array('data' => array('message' => $l->t('No user supplied')) ));
			exit();
		}

		$password = isset($_POST['password']) ? $_POST['password'] : null;
		$recoveryPassword = isset($_POST['recoveryPassword']) ? $_POST['recoveryPassword'] : null;

		if (\OC_User::isAdminUser(\OC_User::getUser())) {
			$userstatus = 'admin';
		} elseif (\OC_SubAdmin::isUserAccessible(\OC_User::getUser(), $username)) {
			$userstatus = 'subadmin';
		} else {
			$l = new \OC_L10n('settings');
			\OC_JSON::error(array('data' => array('message' => $l->t('Authentication error')) ));
			exit();
		}

		if (\OC_App::isEnabled('files_encryption')) {
			//handle the recovery case
			$util = new \OCA\Files_Encryption\Util(new \OC\Files\View('/'), $username);
			$recoveryAdminEnabled = \OC_Appconfig::getValue('files_encryption', 'recoveryAdminEnabled');

			$validRecoveryPassword = false;
			$recoveryEnabledForUser = false;
			if ($recoveryAdminEnabled) {
				$validRecoveryPassword = $util->checkRecoveryPassword($recoveryPassword);
				$recoveryEnabledForUser = $util->recoveryEnabledForUser();
			}

			if ($recoveryEnabledForUser && $recoveryPassword === '') {
				$l = new \OC_L10n('settings');
				\OC_JSON::error(array('data' => array(
					'message' => $l->t('Please provide an admin recovery password, otherwise all user data will be lost')
				)));
			} elseif ($recoveryEnabledForUser && ! $validRecoveryPassword) {
				$l = new \OC_L10n('settings');
				\OC_JSON::error(array('data' => array(
					'message' => $l->t('Wrong admin recovery password. Please check the password and try again.')
				)));
			} else { // now we know that everything is fine regarding the recovery password, let's try to change the password
				$result = \OC_User::setPassword($username, $password, $recoveryPassword);
				if (!$result && $recoveryEnabledForUser) {
					$l = new \OC_L10n('settings');
					\OC_JSON::error(array(
						"data" => array(
							"message" => $l->t("Backend doesn't support password change, but the user's encryption key was successfully updated.")
						)
					));
				} elseif (!$result && !$recoveryEnabledForUser) {
					$l = new \OC_L10n('settings');
					\OC_JSON::error(array("data" => array( "message" => $l->t("Unable to change password" ) )));
				} else {
					\OC_JSON::success(array("data" => array( "username" => $username )));
				}

			}
		} else { // if encryption is disabled, proceed
			if (!is_null($password) && \OC_User::setPassword($username, $password)) {
				\OC_JSON::success(array('data' => array('username' => $username)));
			} else {
				$l = new \OC_L10n('settings');
				\OC_JSON::error(array('data' => array('message' => $l->t('Unable to change password'))));
			}
		}
	}
}
