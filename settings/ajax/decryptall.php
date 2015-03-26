<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Volkan Gezer <volkangezer@gmail.com>
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
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

//encryption app needs to be loaded
OC_App::loadApp('files_encryption');

// init encryption app
$params = array('uid' => \OCP\User::getUser(),
				'password' => (string)$_POST['password']);

$view = new OC\Files\View('/');
$util = new \OCA\Files_Encryption\Util($view, \OCP\User::getUser());
$l = \OC::$server->getL10N('settings');

$result = $util->initEncryption($params);

if ($result !== false) {

	try {
		$successful = $util->decryptAll();
	} catch (\Exception $ex) {
		\OCP\Util::writeLog('encryption library', "Decryption finished unexpected: " . $ex->getMessage(), \OCP\Util::ERROR);
		$successful = false;
	}

	$util->closeEncryptionSession();

	if ($successful === true) {
		\OCP\JSON::success(array('data' => array('message' => $l->t('Files decrypted successfully'))));
	} else {
		\OCP\JSON::error(array('data' => array('message' => $l->t('Couldn\'t decrypt your files, please check your owncloud.log or ask your administrator'))));
	}
} else {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Couldn\'t decrypt your files, check your password and try again'))));
}

