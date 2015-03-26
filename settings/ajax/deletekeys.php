<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
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

$l = \OC::$server->getL10N('settings');

$util = new \OCA\Files_Encryption\Util(new \OC\Files\View(), \OC_User::getUser());
$result = $util->deleteBackup('decryptAll');

if ($result) {
	\OCP\JSON::success(array('data' => array('message' => $l->t('Encryption keys deleted permanently'))));
} else {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Couldn\'t permanently delete your encryption keys, please check your owncloud.log or ask your administrator'))));
}
