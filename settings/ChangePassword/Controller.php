<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author cmeh <cmeh@users.noreply.github.com>
 * @author Florin Peter <github@florin-peter.de>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Sam Tuke <mail@samtuke.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
			\OC::$server->getUserSession()->updateSessionTokenPassword($password);
			\OC_JSON::success();
		} else {
			\OC_JSON::error();
		}
	}

	public static function changeUserPassword($args) {
		// Check if we are an user
		\OC_JSON::callCheck();
		\OC_JSON::checkLoggedIn();

		$l = new \OC_L10n('settings');
		if (isset($_POST['username'])) {
			$username = $_POST['username'];
		} else {
			\OC_JSON::error(array('data' => array('message' => $l->t('No user supplied')) ));
			exit();
		}

		$password = isset($_POST['password']) ? $_POST['password'] : null;
		$recoveryPassword = isset($_POST['recoveryPassword']) ? $_POST['recoveryPassword'] : null;

		$isUserAccessible = false;
		$currentUserObject = \OC::$server->getUserSession()->getUser();
		$targetUserObject = \OC::$server->getUserManager()->get($username);
		if($currentUserObject !== null && $targetUserObject !== null) {
			$isUserAccessible = \OC::$server->getGroupManager()->getSubAdmin()->isUserAccessible($currentUserObject, $targetUserObject);
		}

		if (\OC_User::isAdminUser(\OC_User::getUser())) {
			$userstatus = 'admin';
		} elseif ($isUserAccessible) {
			$userstatus = 'subadmin';
		} else {
			\OC_JSON::error(array('data' => array('message' => $l->t('Authentication error')) ));
			exit();
		}

		if (\OC_App::isEnabled('encryption')) {
			//handle the recovery case
			$crypt = new \OCA\Encryption\Crypto\Crypt(
				\OC::$server->getLogger(),
				\OC::$server->getUserSession(),
				\OC::$server->getConfig(),
				\OC::$server->getL10N('encryption'));
			$keyStorage = \OC::$server->getEncryptionKeyStorage();
			$util = new \OCA\Encryption\Util(
				new \OC\Files\View(),
				$crypt,
				\OC::$server->getLogger(),
				\OC::$server->getUserSession(),
				\OC::$server->getConfig(),
				\OC::$server->getUserManager());
			$keyManager = new \OCA\Encryption\KeyManager(
				$keyStorage,
				$crypt,
				\OC::$server->getConfig(),
				\OC::$server->getUserSession(),
				new \OCA\Encryption\Session(\OC::$server->getSession()),
				\OC::$server->getLogger(),
				$util);
			$recovery = new \OCA\Encryption\Recovery(
				\OC::$server->getUserSession(),
				$crypt,
				\OC::$server->getSecureRandom(),
				$keyManager,
				\OC::$server->getConfig(),
				$keyStorage,
				\OC::$server->getEncryptionFilesHelper(),
				new \OC\Files\View());
			$recoveryAdminEnabled = $recovery->isRecoveryKeyEnabled();

			$validRecoveryPassword = false;
			$recoveryEnabledForUser = false;
			if ($recoveryAdminEnabled) {
				$validRecoveryPassword = $keyManager->checkRecoveryPassword($recoveryPassword);
				$recoveryEnabledForUser = $recovery->isRecoveryEnabledForUser($username);
			}

			if ($recoveryEnabledForUser && $recoveryPassword === '') {
				\OC_JSON::error(array('data' => array(
					'message' => $l->t('Please provide an admin recovery password, otherwise all user data will be lost')
				)));
			} elseif ($recoveryEnabledForUser && ! $validRecoveryPassword) {
				\OC_JSON::error(array('data' => array(
					'message' => $l->t('Wrong admin recovery password. Please check the password and try again.')
				)));
			} else { // now we know that everything is fine regarding the recovery password, let's try to change the password
				$result = \OC_User::setPassword($username, $password, $recoveryPassword);
				if (!$result && $recoveryEnabledForUser) {
					\OC_JSON::error(array(
						"data" => array(
							"message" => $l->t("Backend doesn't support password change, but the user's encryption key was successfully updated.")
						)
					));
				} elseif (!$result && !$recoveryEnabledForUser) {
					\OC_JSON::error(array("data" => array( "message" => $l->t("Unable to change password" ) )));
				} else {
					\OC_JSON::success(array("data" => array( "username" => $username )));
				}

			}
		} else { // if encryption is disabled, proceed
			if (!is_null($password) && \OC_User::setPassword($username, $password)) {
				\OC_JSON::success(array('data' => array('username' => $username)));
			} else {
				\OC_JSON::error(array('data' => array('message' => $l->t('Unable to change password'))));
			}
		}
	}
}
